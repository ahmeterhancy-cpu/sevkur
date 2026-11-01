<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Import\ProductIdentity;
use App\Services\Import\ProductName;
use App\Services\Import\RemoteFetcher;
use Illuminate\Console\Command;

/**
 * vespero.com urun sayfalarini magazaya aktarir.
 *
 * Site WordPress; urunler WooCommerce urunu degil, duz SAYFA olarak
 * yayinlanmis. Dolayisiyla fiyat ve stok yok — sayfadan yalnizca urun adi ve
 * NextGEN galerisindeki fotograflar aliniyor.
 *
 * Galeri fotograflarinin sayfadaki adresleri kucuk onizlemelerdir
 * (".../cache/...-240x160x100-....webp"); tam boyutlusu ayni klasorde,
 * cache ve thumbs olmadan durur. Kucuk olani indirmek vitrinde bulanik
 * gorsel demek oldugu icin yalnizca tam boyutlular aliniyor.
 *
 * Istekler arasinda bekleme var: baskasinin sunucusunu yormadan gezmek icin.
 */
class ImportVesperoCatalogue extends Command
{
    protected $signature = 'vespero:aktar
        {--limit=0 : Yalnizca ilk N urun (deneme icin)}
        {--bekle=400 : Istekler arasi bekleme (ms)}
        {--kuru : Hicbir sey yazma, ne yapilacagini goster}';

    protected $description = 'vespero.com ürün sayfalarını taslak olarak ekler';

    private const SITEMAP = 'https://www.vespero.com/wp-sitemap-posts-page-1.xml';

    /** Urun sayfasi olmayan kurumsal sayfalar. */
    private const SKIP = [
        'gizlilik', 'iletisim', 'hakkimizda', 'ticari', 'e-katalog',
        'kvkk', 'mesafeli', 'iade', 'sitemap', 'blog',
    ];

    /** Slug'daki anahtar kelimeye gore magaza kategorisi. */
    private const CATEGORIES = [
        'kose-takimi' => 'kose-takimlari',
        'koltuk-takimi' => 'koltuk-takimlari',
        'maksi-takim' => 'koltuk-takimlari',
        'suryat' => 'koltuk-takimlari',
        'kanepe' => 'koltuk-takimlari',
        'berjer' => 'berjer-ve-koltuk',
        'puf' => 'berjer-ve-koltuk',
        'sehpa' => 'sehpalar',
        'yatak' => 'karyolalar',
    ];

    public function handle(RemoteFetcher $fetcher, ProductIdentity $identity, ProductName $names): int
    {
        // Yuz urunun fotografi tek surecte isleniyor; varsayilan 128 MB
        // yetmedi (ilk calistirmada 73. uruncude tukendi).
        if ((int) ini_get('memory_limit') < 512) {
            ini_set('memory_limit', '512M');
        }

        $dry = (bool) $this->option('kuru');
        $wait = max(0, (int) $this->option('bekle')) * 1000;
        $limit = (int) $this->option('limit');

        $urls = $this->productUrls($fetcher);

        if ($urls === []) {
            $this->error('Ürün adresleri alınamadı.');

            return self::FAILURE;
        }

        if ($limit > 0) {
            $urls = array_slice($urls, 0, $limit);
        }

        $this->info(count($urls).' ürün sayfası bulundu.');

        $brand = $dry ? null : $this->brand();

        $created = 0;
        $updated = 0;
        $failed = [];

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        foreach ($urls as $url) {
            $page = $fetcher->html($url);

            if (! $page['ok']) {
                $failed[] = basename(rtrim($url, '/')).' ('.$page['error'].')';
                $bar->advance();

                continue;
            }

            // h1 uc sayfada baska urunu gosteriyor; adresle uyusan ad secilir.
            [$name, $wrongName] = $names->resolve($page['body'], $url, ['- Vespero']);
            $images = $this->galleryImages($page['body']);

            if ($name === null || $images === []) {
                $failed[] = basename(rtrim($url, '/')).' (ad veya görsel yok)';
                $bar->advance();

                continue;
            }

            if ($dry) {
                $this->newLine();
                $this->line(sprintf('  %-34s %-20s %d görsel', $name, $this->category($url), count($images)));
                $created++;
                $bar->advance();

                continue;
            }

            // Kimlik slug degil KAYNAK ADRESI: "Armani Koltuk Takimi" adi
            // Sude aktariminda bu markanin urununu devralmisti.
            $existing = $identity->existing($url);
            $slug = $existing?->slug ?? $identity->slug($name, 'vespero');

            $stored = [];

            foreach (array_slice($images, 0, 4) as $index => $imageUrl) {
                $relative = sprintf('urunler/vespero/%s-%d.jpg', $slug, $index);

                // Komut yarida kalirsa bastan calistirilabilsin: inmis
                // fotograf yeniden indirilmez.
                if (is_file(public_path($relative))) {
                    $stored[] = $relative;

                    continue;
                }

                $target = $this->storeImage($fetcher, $imageUrl, $slug, $index);

                if ($target !== null) {
                    $stored[] = $target;
                }

                usleep($wait);
            }

            if ($stored === []) {
                $failed[] = $name.' (görsel indirilemedi)';
                $bar->advance();

                continue;
            }

            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $this->category($url))->value('id'),
                'brand_id' => $brand?->id,
                'name' => ['tr' => $name],
                'short_description' => ['tr' => 'Vespero koleksiyonundan.'],
                'description' => ['tr' => $name.', Vespero koleksiyonundan. Kumaş ve renk seçenekleri için bizimle iletişime geçin.'],
                'price' => 0,
                'manage_stock' => false,
                'stock' => 0,
                'cover' => $stored[0],
                'hover_image' => $stored[1] ?? null,
                'is_active' => true,
                'published_at' => now(),
                'meta' => ['kaynak' => 'vespero.com', 'kaynak_adres' => $url],
            ]);

            // Kalan fotograflar galeriye
            if (count($stored) > 2) {
                $product->images()->delete();

                foreach (array_slice($stored, 2) as $position => $path) {
                    $product->images()->create(['path' => $path, 'position' => $position]);
                }
            }

            $product->wasRecentlyCreated ? $created++ : $updated++;

            unset($product, $page, $images);
            gc_collect_cycles();

            $bar->advance();
            usleep($wait);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($dry ? '[KURU] ' : '').$created.' ürün eklendi, '.$updated.' ürün güncellendi.');

        if ($failed !== []) {
            $this->warn(count($failed).' sayfa atlandı: '.implode(', ', array_slice($failed, 0, 8)).(count($failed) > 8 ? '…' : ''));
        }

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function productUrls(RemoteFetcher $fetcher): array
    {
        $map = $fetcher->xml(self::SITEMAP);

        if (! $map['ok']) {
            return [];
        }

        preg_match_all('#<loc>(https://www\.vespero\.com/[^<]+)</loc>#', $map['body'], $m);

        $urls = [];

        foreach ($m[1] as $url) {
            $slug = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');

            if ($slug === '' || str_contains($slug, '/')) {
                continue;
            }

            foreach (self::SKIP as $skip) {
                if (str_contains($slug, $skip)) {
                    continue 2;
                }
            }

            $urls[] = $url;
        }

        return array_values(array_unique($urls));
    }


    /**
     * Galerideki TAM BOYUT fotograflar.
     *
     * @return array<int, string>
     */
    private function galleryImages(string $html): array
    {
        preg_match_all(
            '#https://www\.vespero\.com/wp-content/(?:gallery|uploads)/[^"\'?\s]+\.(?:webp|jpg|jpeg|png)#i',
            $html,
            $m
        );

        $images = [];

        foreach ($m[0] as $url) {
            // Onizlemeler ve arayuz gorselleri disarida
            if (preg_match('#/(cache|thumbs)/|thumbs_|logo|icon|favicon|placeholder|-\d{2,4}x\d{2,4}\.#i', $url)) {
                continue;
            }

            $images[$url] = true;
        }

        return array_keys($images);
    }

    private function category(string $url): string
    {
        $slug = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        foreach (self::CATEGORIES as $keyword => $category) {
            if (str_contains($slug, $keyword)) {
                return $category;
            }
        }

        return 'koltuk-takimlari';
    }

    /** Fotografi indirir, JPEG'e cevirip magaza medyasina yazar. */
    private function storeImage(RemoteFetcher $fetcher, string $url, string $slug, int $index): ?string
    {
        $response = $fetcher->image($url);

        if (! $response['ok']) {
            return null;
        }

        // Site WebP sunuyor; vitrin JPEG kullaniyor
        $image = @imagecreatefromstring($response['body']);

        if ($image === false) {
            return null;
        }

        if (imagesx($image) < 500) {
            imagedestroy($image);

            return null;
        }

        if (imagesx($image) > 1400) {
            $scaled = imagescale($image, 1400);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        $relative = sprintf('urunler/vespero/%s-%d.jpg', $slug, $index);
        $target = public_path($relative);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        $ok = imagejpeg($image, $target, 86);
        imagedestroy($image);

        return $ok ? $relative : null;
    }

    private function brand(): Brand
    {
        return Brand::updateOrCreate(['slug' => 'vespero'], [
            'name' => ['tr' => 'Vespero', 'en' => 'Vespero', 'bg' => 'Vespero', 'el' => 'Vespero'],
            'is_active' => true,
            'position' => 12,
        ]);
    }
}
