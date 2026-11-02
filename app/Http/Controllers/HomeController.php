<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    /*
     * Ana sayfa olculeri yonetimden ayarlanir (Magaza Ayarlari > Ana sayfa).
     * Buradaki degerler yalnizca ayar girilmemisse kullanilan varsayilanlar.
     */

    /** Ana sayfada kac oda rayi gosterilir. */
    private const RAIL_COUNT = 3;

    /** Bir rayda kac urun. */
    private const RAIL_SIZE = 10;

    /** Bu kadar urunu olmayan kategori ray acmaz — yari bos ray kotu durur. */
    private const RAIL_MIN_PRODUCTS = 8;

    /** Kategori vitrinindeki kutu sayisi. */
    private const CATEGORY_TILES = 5;

    /** Kacinci ray bolunmus duzende cizilir; 0 = hicbiri. */
    private const RAIL_SPLIT = 2;

    private function railCount(): int
    {
        return max(0, min(6, (int) setting('home_rails_count', self::RAIL_COUNT)));
    }

    private function railSize(): int
    {
        return max(4, min(24, (int) setting('home_rails_size', self::RAIL_SIZE)));
    }

    private function railMinProducts(): int
    {
        return max(1, (int) setting('home_rails_min', self::RAIL_MIN_PRODUCTS));
    }

    /**
     * Kacinci ray bolunmus duzende cizilir (1 = ilk ray, 0 = hicbiri).
     *
     * Ayni yapidaki raylar art arda gelince sayfa tekduze duruyor; bir ray
     * solda odanin fotografi, sagda urunler olacak sekilde boluyor.
     */
    private function railSplit(): int
    {
        return max(0, min($this->railCount(), (int) setting('home_rail_split', self::RAIL_SPLIT)));
    }

    public function index()
    {
        // Satis verisi olmayan urunu "cok satan" diye gostermek musteriye
        // yanlis bilgi verir; ayrica hepsinin sold_count'u 0 oldugunda siralama
        // "en son eklenenler"e dusup Yeni Gelenler bolumunun AYNISI olur.
        // Hic satis yoksa bolum gorunmez (gorunum isNotEmpty ile korunuyor).
        $bestSellers = Product::active()->with(['category', 'variants'])
            ->where('sold_count', '>', 0)
            ->orderByDesc('sold_count')->orderByDesc('rating_avg')
            ->take(10)->get();

        $newArrivals = Product::active()->with(['category', 'variants'])
            ->where('is_new', true)
            ->orderByDesc('created_at')
            ->take(10)->get();

        if ($newArrivals->count() < 4) {
            $newArrivals = Product::active()->with(['category', 'variants'])->latest()->take(10)->get();
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
            'categoryRails' => setting('home_rails_enabled', true)
                ? $this->categoryRails()
                : collect(),
            'railSplit' => $this->railSplit(),
            'bestSellers' => $bestSellers,
            'newArrivals' => $newArrivals,
            'deals' => $deals,
            'midBanners' => Banner::active()->placement('home_mid')->get(),
            'gridBanners' => Banner::active()->placement('home_grid')->get(),
            'wideBanner' => Banner::active()->placement('home_wide')->first(),
            'posts' => Post::published()->with('category')->latest('published_at')->take(3)->get(),
        ]);
    }

    /**
     * Oda bazli urun raylari.
     *
     * Katalogda 600'u askin urun var ama ana sayfa yalnizca "Yeni Gelenler"
     * rayini gosteriyordu: fiyat girilmedigi icin "Cok Satanlar" ve
     * "Firsatlar" bolumleri de bos kaliyor, sayfa bu yuzden ince duruyordu.
     * En dolu uc oda kategorisinin urunleri birer rayda gosteriliyor.
     *
     * Urunler dort sorguda toplaniyor: bir sayim, uc ray. Kategori basina
     * sorgu acilmiyor.
     *
     * @return Collection<int, array{kategori: Category, urunler: Collection}>
     */
    private function categoryRails()
    {
        $counts = Product::active()
            ->selectRaw('category_id, count(*) as toplam')
            ->groupBy('category_id')
            ->pluck('toplam', 'category_id');

        $roots = Category::active()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get();

        // Yonetici kategorilerden birini isaretlediyse ray onlarindir: sira
        // kategori sirasi, sayi da secilen kadar. Isaretli kategori yoksa
        // eski davranis surer — en dolu odalar kendiliginden secilir.
        $secilmis = $roots->where('show_home_rail', true);
        $elle = $secilmis->isNotEmpty();

        return ($elle ? $secilmis : $roots)
            ->map(function (Category $root) use ($counts) {
                $ids = collect($root->descendantIds());

                return [
                    'kategori' => $root,
                    'toplam' => $ids->sum(fn (int $id) => (int) ($counts[$id] ?? 0)),
                    'ids' => $ids,
                ];
            })
            // Urunu olmayan kategori bos ray cizer. Yonetici acikca sectiyse
            // az urunlu olmasi engel degil, ama sifir urun yine de gecmez.
            ->filter(fn (array $row) => $row['toplam'] >= ($elle ? 1 : $this->railMinProducts()))
            ->when(! $elle, fn ($rows) => $rows->sortByDesc('toplam')->take($this->railCount()))
            ->map(fn (array $row) => [
                'kategori' => $row['kategori'],
                'toplam' => $row['toplam'],
                'urunler' => $this->spreadAcrossSubcategories($row['ids']),
            ])
            ->values();
    }

    /**
     * Ray urunlerini ALT KATEGORILER arasinda dagitir.
     *
     * Dogrudan son eklenenleri almak odayi temsil etmiyordu: aktarim ayni
     * seriyi arka arkaya yazdigi icin "Yatak Odasi" rayinda ayni pembe yatak
     * takiminin on fotografi cikiyordu. Alt kategoriler sirayla gezilerek
     * karyola, gardirop, komodin, baza… yan yana geliyor.
     *
     * @param  Collection<int, int>  $categoryIds
     */
    private function spreadAcrossSubcategories($categoryIds)
    {
        // Tek sorgu: her alt kategoriden yetecek kadar aday.
        $candidates = Product::active()
            ->with(['category', 'variants'])
            ->whereIn('category_id', $categoryIds)
            ->whereNotNull('cover')
            ->latest('id')
            ->take($this->railSize() * 12)
            ->get()
            ->groupBy('category_id');

        $picked = collect();
        $usedSeries = [];

        // Sirayla her alt kategoriden birer tane al, ray dolana kadar don.
        $size = $this->railSize();

        while ($picked->count() < $size && $candidates->isNotEmpty()) {
            foreach ($candidates as $categoryId => $group) {
                if ($picked->count() >= $size) {
                    break;
                }

                // Her alt kategorinin EN YENISI cogu zaman ayni seriden
                // (aktarim seriyi toplu yaziyor): rayin ilk ekrani "Vera
                // Baslik, Vera Baza, Vera Yatak…" oluyordu. Gorulmemis bir
                // seri varsa once o alinir.
                $index = $group->search(
                    fn (Product $p) => ! isset($usedSeries[$this->series($p)])
                );

                $product = $index === false ? $group->shift() : $group->pull($index);

                $usedSeries[$this->series($product)] = true;
                $picked->push($product);

                if ($group->isEmpty()) {
                    $candidates->forget($categoryId);
                }
            }

            $candidates = $candidates->map(fn ($group) => $group->values());
        }

        return $picked->values();
    }

    /** Urun adinin ilk kelimesi seri adidir: "Vera Baza" -> "vera". */
    private function series(Product $product): string
    {
        return mb_strtolower(explode(' ', trim($product->t('name')))[0] ?? '', 'UTF-8');
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
            ->get();

        $counts = Product::active()
            ->selectRaw('category_id, count(*) as toplam')
            ->groupBy('category_id')
            ->pluck('toplam', 'category_id');

        foreach ($categories as $category) {
            $category->product_total = collect($category->descendantIds())
                ->sum(fn (int $id) => (int) ($counts[$id] ?? 0));
        }

        // Urunu olmayan kategori vitrine girmez: karti tiklayan musteri bos
        // sayfaya dusuyordu, ustelik gosterecek urun fotografi olmadigi icin
        // kartta kurulumdan kalma demo gorsel kaliyordu (Aydinlatma'da
        // antika terazi, Ev Tekstili'nde muzelik kilim). Urun girilince
        // kendiliginden geri gelirler.
        // BES kutu: mozaikte ilki 2x2 yer kapliyor, kalan dordu 4x2
        // izgarayi tam kapatiyor. Alti kutu alt satirda bosluk birakirdi.
        return $categories
            ->filter(fn (Category $category) => $category->product_total > 0)
            ->take(max(3, min(5, (int) setting('home_categories_count', self::CATEGORY_TILES))))
            ->values();
    }
}
