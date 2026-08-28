<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;

class HomeController extends Controller
{
    public function index()
    {
        $bestSellers = Product::active()->with(['category', 'variants'])
            ->orderByDesc('sold_count')->orderByDesc('rating_avg')
            ->take(8)->get();

        $newArrivals = Product::active()->with(['category', 'variants'])
            ->where('is_new', true)
            ->orderByDesc('created_at')
            ->take(8)->get();

        if ($newArrivals->count() < 4) {
            $newArrivals = Product::active()->with(['category', 'variants'])->latest()->take(8)->get();
        }

        // Sayacli firsat urunleri (Furnob'daki "Sold X / Y" bloklari).
        $deals = Product::active()->with(['category', 'variants'])
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price')
            ->orderByRaw('CASE WHEN sale_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sale_ends_at')
            ->take(4)->get();

        return view('pages.home', [
            'sliders' => Slider::active()->get(),
            'featuredCategories' => $this->featuredCategories(),
            'bestSellers' => $bestSellers,
            'newArrivals' => $newArrivals,
            'featured' => Product::active()->with(['category', 'variants'])->where('is_featured', true)->orderBy('position')->take(8)->get(),
            'deals' => $deals,
            'midBanners' => Banner::active()->placement('home_mid')->get(),
            'gridBanners' => Banner::active()->placement('home_grid')->get(),
            'wideBanner' => Banner::active()->placement('home_wide')->first(),
            'posts' => Post::published()->with('category')->latest('published_at')->take(3)->get(),
        ]);
    }

    /**
     * Ana sayfadaki kategori vitrini.
     *
     * Urun sayisi ALT KATEGORILERI de kapsar: urunler "Koltuk Takimlari" gibi
     * alt kategorilere bagli, ana kategoriye dogrudan bagli degil. Sayim tek
     * sorguda yapilip bellekte eslestirilir; kategori basina sorgu acilmaz.
     */
    private function featuredCategories()
    {
        $categories = Category::active()
            ->where('is_featured', true)
            ->orderBy('position')
            ->with('children')
            ->take(6)
            ->get();

        $counts = Product::active()
            ->selectRaw('category_id, count(*) as toplam')
            ->groupBy('category_id')
            ->pluck('toplam', 'category_id');

        foreach ($categories as $category) {
            $category->product_total = collect($category->descendantIds())
                ->sum(fn (int $id) => (int) ($counts[$id] ?? 0));
        }

        return $categories;
    }
}
