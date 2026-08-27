<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    private const PER_PAGE_OPTIONS = [12, 24, 36];

    public function index(Request $request)
    {
        return $this->renderList($request, null);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        return $this->renderList($request, $category);
    }

    public function search(Request $request)
    {
        return $this->renderList($request, null, true);
    }

    private function renderList(Request $request, ?Category $category, bool $isSearch = false)
    {
        $query = Product::active()->with(['category', 'brand']);

        if ($category) {
            $query->whereIn('category_id', $category->descendantIds());
        }

        $term = trim((string) $request->query('q'));

        if ($term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $like = '%'.mb_strtolower($term).'%';
                $q->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(short_description) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$like]);
            });
        }

        // Marka
        $brandSlugs = array_filter((array) $request->query('marka', []));
        if ($brandSlugs) {
            $query->whereHas('brand', fn (Builder $q) => $q->whereIn('slug', $brandSlugs));
        }

        // Fiyat araligi (gorunen para biriminde degil, TRY olarak filtrelenir)
        $min = $request->query('min');
        $max = $request->query('max');
        if (is_numeric($min)) {
            $query->where('price', '>=', (float) $min);
        }
        if (is_numeric($max)) {
            $query->where('price', '<=', (float) $max);
        }

        // Ozellik suzgecleri: ?ozellik[renk][]=ceviz
        $attributeFilters = (array) $request->query('ozellik', []);
        foreach ($attributeFilters as $attributeSlug => $values) {
            $values = array_filter((array) $values);

            if (! $values) {
                continue;
            }

            $query->whereHas('attributeValues', function (Builder $q) use ($attributeSlug, $values) {
                $q->whereIn('attribute_values.slug', $values)
                    ->whereHas('attribute', fn (Builder $a) => $a->where('slug', $attributeSlug));
            });
        }

        if ($request->boolean('stokta')) {
            $query->inStock();
        }

        if ($request->boolean('indirimli')) {
            $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
        }

        $sort = $request->query('sirala', 'onerilen');

        match ($sort) {
            'fiyat-artan' => $query->orderBy('price'),
            'fiyat-azalan' => $query->orderByDesc('price'),
            'yeni' => $query->orderByDesc('created_at'),
            'populer' => $query->orderByDesc('sold_count'),
            'puan' => $query->orderByDesc('rating_avg'),
            default => $query->orderBy('position')->orderByDesc('is_featured')->orderByDesc('created_at'),
        };

        $perPage = (int) $request->query('adet', 12);
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 12;

        $products = $query->paginate($perPage)->withQueryString();

        $priceBounds = Product::active()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return view('pages.shop', [
            'products' => $products,
            'category' => $category,
            'categories' => Category::active()->roots()->with('activeChildren')->get(),
            'brands' => Brand::active()->orderBy('position')->get(),
            'attributes' => Attribute::where('is_filterable', true)->with('values')->orderBy('position')->get(),
            'sort' => $sort,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'term' => $term,
            'isSearch' => $isSearch,
            'minPrice' => (float) ($priceBounds->min_price ?? 0),
            'maxPrice' => (float) ($priceBounds->max_price ?? 0),
            'activeView' => $request->query('gorunum', 'grid'),
        ]);
    }

    public function product(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images', 'variants', 'category.parent', 'brand',
            'attributeValues.attribute', 'approvedReviews',
        ]);

        $product->incrementQuietly('views');

        $related = $product->related()->active()->take(8)->get();

        if ($related->isEmpty() && $product->category_id) {
            $related = Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->inRandomOrder()
                ->take(8)->get();
        }

        return view('pages.product', [
            'product' => $product,
            'related' => $related,
            'ratingBreakdown' => $this->ratingBreakdown($product),
        ]);
    }

    public function quickView(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'variants']);

        return view('partials.quick-view', ['product' => $product]);
    }

    /** @return array<int, array{stars: int, count: int, percent: int}> */
    private function ratingBreakdown(Product $product): array
    {
        $counts = $product->reviews()->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')->pluck('c', 'rating')->all();

        $total = array_sum($counts) ?: 1;
        $rows = [];

        for ($stars = 5; $stars >= 1; $stars--) {
            $count = (int) ($counts[$stars] ?? 0);
            $rows[] = [
                'stars' => $stars,
                'count' => $count,
                'percent' => (int) round($count / $total * 100),
            ];
        }

        return $rows;
    }

    public function storeReview(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $product->reviews()->create($data + [
            'user_id' => auth()->id(),
            'is_approved' => false,
        ]);

        return back()->with('status', __('shop.review_submitted'));
    }

    public function brands()
    {
        return view('pages.brands', [
            'brands' => Brand::active()->withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('position')->get(),
        ]);
    }
}
