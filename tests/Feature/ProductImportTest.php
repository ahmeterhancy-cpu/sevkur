<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Import\ProductImporter;
use App\Services\Import\RemoteFetcher;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ShopTestCase;

class ProductImportTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testte gercek DNS sorgusu yapilmasin; koruma mantigi aynen calisir
        app(RemoteFetcher::class)->resolveUsing(
            fn (string $host) => $host === 'ornek.test' ? ['93.184.216.34'] : []
        );
    }

    private function importer(): ProductImporter
    {
        return app(ProductImporter::class);
    }

    // ------------------------------------------------------------- ayristirma

    public function test_reads_product_from_json_ld(): void
    {
        $html = <<<'HTML'
        <html><head>
        <script type="application/ld+json">
        {"@context":"https://schema.org","@graph":[
          {"@type":"WebPage","name":"Sayfa"},
          {"@type":"Product","name":"Meriç Koltuk","sku":"MK-1",
           "description":"Masif ahşap gövde.",
           "brand":{"@type":"Brand","name":"Sevkur"},
           "image":["https://ornek.test/a.jpg","https://ornek.test/b.jpg"],
           "offers":{"@type":"Offer","price":"38900.00","priceCurrency":"TRY"}}
        ]}
        </script></head><body></body></html>
        HTML;

        Http::fake(['ornek.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/urun/meric');

        $this->assertSame('Meriç Koltuk', $product->name);
        $this->assertSame('MK-1', $product->sku);
        $this->assertSame('Sevkur', $product->brand);
        $this->assertSame(38900.0, $product->price);
        $this->assertSame('TRY', $product->currency);
        $this->assertSame(['https://ornek.test/a.jpg', 'https://ornek.test/b.jpg'], $product->images);
    }

    public function test_reads_woocommerce_markup_when_json_ld_missing(): void
    {
        $html = <<<'HTML'
        <html><body>
          <h1 class="product_title">Selimiye Berjer</h1>
          <div class="summary">
            <p class="price">
              <del><span class="woocommerce-Price-amount">17.260,00 &#8378;</span></del>
              <ins><span class="woocommerce-Price-amount">14.630,00 &#8378;</span></ins>
            </p>
            <div class="woocommerce-product-details__short-description">Kadife döşeme.</div>
          </div>
          <div class="woocommerce-product-gallery">
            <img data-large_image="https://ornek.test/buyuk-1.jpg" src="https://ornek.test/kucuk-1.jpg">
            <img src="/goreli-2.jpg">
          </div>
        </body></html>
        HTML;

        Http::fake(['ornek.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/urun/selimiye');

        $this->assertSame('Selimiye Berjer', $product->name);
        $this->assertSame('Kadife döşeme.', $product->shortDescription);
        $this->assertSame(14630.0, $product->price);
        $this->assertSame(17260.0, $product->compareAtPrice);
        $this->assertContains('https://ornek.test/buyuk-1.jpg', $product->images);
        // Goreli adres mutlaklastirilmali
        $this->assertContains('https://ornek.test/goreli-2.jpg', $product->images);
    }

    public function test_falls_back_to_open_graph(): void
    {
        $html = '<html><head>'
            .'<meta property="og:title" content="Kaleiçi Sehpa">'
            .'<meta property="og:description" content="Ceviz kaplama.">'
            .'<meta property="og:image" content="https://ornek.test/og.jpg">'
            .'<meta property="product:price:amount" content="6450">'
            .'<meta property="product:price:currency" content="EUR">'
            .'</head><body></body></html>';

        Http::fake(['ornek.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/urun/kaleici');

        $this->assertSame('Kaleiçi Sehpa', $product->name);
        $this->assertSame(6450.0, $product->price);
        $this->assertSame('EUR', $product->currency);

        // Farkli para birimi uyarisi cikmali
        $this->assertNotEmpty(array_filter($product->notes, fn ($n) => str_contains($n, 'EUR')));
    }

    public function test_ignores_tracking_pixels_and_icons(): void
    {
        $html = '<html><body><h1>Ürün</h1>'
            .'<img src="https://ornek.test/logo.png">'
            .'<img src="https://ornek.test/tracking-pixel.gif">'
            .'<img src="https://ornek.test/gercek-gorsel.jpg">'
            .'</body></html>';

        Http::fake(['ornek.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/urun/x');

        $this->assertSame(['https://ornek.test/gercek-gorsel.jpg'], $product->images);
    }

    public function test_reports_when_page_is_not_a_product(): void
    {
        Http::fake(['ornek.test/*' => Http::response('<html><body><p>merhaba</p></body></html>', 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/hakkimizda');

        $this->assertFalse($product->hasEnough());
        $this->assertNotEmpty($product->notes);
    }

    public function test_reads_sale_price_from_dom_when_json_ld_only_has_current(): void
    {
        // Gercek WooCommerce sayfalarinda JSON-LD YALNIZCA guncel fiyati verir;
        // ustu cizili fiyat sadece isaretlemede bulunur.
        $html = <<<'HTML'
        <html><head>
        <script type="application/ld+json">
        {"@type":"Product","name":"Delphine Berjer",
         "offers":{"@type":"Offer","price":"249.70","priceCurrency":"USD"}}
        </script></head>
        <body><div class="summary">
          <p class="price">
            <del><span class="woocommerce-Price-amount amount">$289.70</span></del>
            <ins><span class="woocommerce-Price-amount amount">$249.70</span></ins>
          </p>
        </div></body></html>
        HTML;

        Http::fake(['ornek.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $product = $this->importer()->preview('https://ornek.test/urun/delphine');

        $this->assertSame(249.70, $product->price);
        $this->assertSame(289.70, $product->compareAtPrice);
    }

    // ------------------------------------------------------------ SSRF koruma

    public static function unsafeUrls(): array
    {
        return [
            'yerel ad' => ['http://localhost/urun'],
            'geri dongu' => ['http://127.0.0.1:8000/urun'],
            'ozel ag 10' => ['http://10.0.0.5/urun'],
            'ozel ag 192' => ['http://192.168.1.10/urun'],
            'bulut metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'dosya semasi' => ['file:///etc/passwd'],
            'ftp semasi' => ['ftp://ornek.test/urun'],
            'kapali port' => ['http://ornek.test:22/urun'],
        ];
    }

    #[DataProvider('unsafeUrls')]
    public function test_rejects_unsafe_addresses(string $url): void
    {
        $this->assertNotNull(
            app(RemoteFetcher::class)->guard($url),
            "Bu adres reddedilmeliydi: $url"
        );
    }

    public function test_importer_does_not_fetch_unsafe_addresses(): void
    {
        Http::preventStrayRequests();

        // Istek hic cikmamali; ciksaydi preventStrayRequests hata verirdi
        $product = $this->importer()->preview('http://127.0.0.1/urun');

        $this->assertFalse($product->hasEnough());
        $this->assertNotEmpty($product->notes);
    }

    // ---------------------------------------------------------------- kayit

    public function test_creates_a_draft_product_with_images(): void
    {
        $pixel = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        Http::fake([
            'ornek.test/gorsel-*' => Http::response($pixel, 200, ['Content-Type' => 'image/png']),
        ]);

        $category = Category::where('slug', 'koltuk-takimlari')->firstOrFail();

        $result = $this->importer()->create([
            'source_url' => 'https://ornek.test/urun/yeni',
            'name' => 'İçe Aktarılan Koltuk',
            'sku' => 'IA-1',
            'category_id' => $category->id,
            'price' => 12345.67,
            'compare_at_price' => 14000,
            'stock' => 4,
            'short_description' => 'Kısa',
            'description' => 'Uzun açıklama',
            'selected_images' => [
                'https://ornek.test/gorsel-1.png',
                'https://ornek.test/gorsel-2.png',
                'https://ornek.test/gorsel-3.png',
            ],
        ]);

        $product = $result['product'];

        $this->assertSame(3, $result['downloaded']);
        $this->assertSame('İçe Aktarılan Koltuk', $product->t('name'));
        $this->assertSame(12345.67, (float) $product->price);
        $this->assertSame($category->id, $product->category_id);

        // Taslak olarak gelmeli — kontrolsuz yayina cikmasin
        $this->assertFalse($product->is_active);
        $this->assertNull($product->published_at);

        $this->assertNotNull($product->cover);
        $this->assertNotNull($product->hover_image);
        $this->assertSame(1, $product->images()->count());

        // Kaynak adres kayitli kalsin
        $this->assertSame('https://ornek.test/urun/yeni', $product->meta['import_source'] ?? null);

        foreach ([$product->cover, $product->hover_image] as $path) {
            $this->assertFileExists(public_path($path));
        }
    }

    public function test_generates_a_unique_slug_when_name_collides(): void
    {
        Http::preventStrayRequests();

        $existing = $this->product(1);

        $first = $this->importer()->create([
            'name' => $existing->t('name'),
            'price' => 100,
            'selected_images' => [],
        ]);

        $this->assertNotSame($existing->slug, $first['product']->slug);
        $this->assertSame(2, Product::where('name->tr', $existing->t('name'))->count());
    }

    // ----------------------------------------------------------------- panel

    public function test_import_page_is_reachable_for_staff_only(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail())
            ->get('/admin/import-product')
            ->assertSuccessful();

        $this->actingAs(User::where('email', 'musteri@example.com')->firstOrFail())
            ->get('/admin/import-product')
            ->assertForbidden();
    }
}
