<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Import\CategoryFromPath;
use App\Services\Import\ImageStore;
use App\Services\Import\ProductIdentity;
use App\Services\Import\ProductName;
use App\Services\Import\RemoteFetcher;
use Illuminate\Console\Command;

/**
 * Tedarikci sitelerinden urun aktarir (config/import-sites.php).
 *
 * Urun kumesi SITE HARITASINDAN gelir — kesin liste odur. Kategori ya adresin
 * yol parcasindan ya da kategori sayfalarini gezerek bulunur; urun adindan
 * TAHMIN EDILMEZ (Oben katalogunda sira tahmini dort koleksiyonda yanlis
 * fotografa yol acmisti).
 *
 * Fiyat okunmaz: bu siteler fiyat yayinlamiyor, zaten fiyatlari kullanici
 * girecek. Fiyatsiz urun vitrinde gorunur ama sepete eklenemez
 * (Product::hasPrice + CartService).
 *
 * Istekler arasinda bekleme var: baskasinin sunucusunu yormadan gezmek icin.
 */
class ImportSiteCatalogue extends Command
{
    protected $signature = 'site:aktar
        {site : config/import-sites.php içindeki anahtar}
        {--limit=0 : Yalnızca ilk N ürün (deneme için)}
        {--bekle=300 : İstekler arası bekleme (ms)}
        {--yayinda : Ürünleri yayına al (varsayılan: taslak)}
        {--kuru : Hiçbir şey yazma, ne yapılacağını göster}';

    protected $description = 'Tedarikçi sitesinden ürünleri aktarır';

    /** Bir urunden en fazla kac fotograf alinir (kapak + hover + 2 galeri). */
    private const MAX_PHOTOS = 4;

    /** Kategori sayfalamasinda sonsuz donguye karsi ust sinir. */
    private const MAX_CATEGORY_PAGES = 30;

    public function handle(RemoteFetcher $fetcher, ImageStore $images, ProductIdentity $identity, ProductName $names): int
    {
        $key = $this->argument('site');
        $site = config('import-sites.'.$key);

        if (! is_array($site)) {
            $this->error("Tanımsız site: $key. Tanımlılar: ".implode(', ', array_keys(config('import-sites', []))));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('kuru');
        $wait = max(0, (int) $this->option('bekle')) * 1000;

        // Kategori eslesmesi: adres yol parcasini tasiyorsa oradan okunur,
        // tasimiyorsa kategori sayfalari gezilir.
        $map = isset($site['yoldan_kategori'])
            ? []
            : $this->categoryMap($fetcher, $site, $wait);

        // Ravizza'da urun site haritasi yok (urunler duz WordPress yazisi,
        // yazi haritasi blog yazilariyla karisik) — urun kumesi de ayni
        // kategori gezintisinden cikar.
        $urls = ! empty($site['kategoriden_kesfet'])
            ? array_keys($map)
            : $this->productUrls($fetcher, $site);

        $urls = $this->withoutSkipped($urls, $site);

        if ($urls === []) {
            $this->error('Ürün adresi bulunamadı.');

            return self::FAILURE;
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $urls = array_slice($urls, 0, $limit);
        }

        $this->info(count($urls).' ürün sayfası bulundu.');

        $brand = $dry ? null : $this->brand($key, $site);

        $created = 0;
        $updated = 0;
        $failed = [];
        $suspect = [];

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        foreach ($urls as $url) {
            $page = $fetcher->html($url);

            if (! $page['ok']) {
                $failed[] = $this->label($url).' ('.$page['error'].')';
                $bar->advance();

                continue;
            }

            [$name, $wrongName] = $names->resolve($page['body'], $url, $site['ad_eki'] ?? []);
            $photos = $this->galleryImages($page['body'], $site, $fellBack);
            $category = $this->category($url, $site, $map);

            if ($name === null || $photos === []) {
                $failed[] = $this->label($url).' (ad veya görsel yok)';
                $bar->advance();

                continue;
            }

            if ($fellBack || $wrongName) {
                $suspect[] = $this->label($url).($wrongName ? ' (ad)' : ' (galeri)');
            }

            if ($dry) {
                $this->newLine();
                $this->line(sprintf('  %-44s %-24s %d görsel%s', mb_substr($name, 0, 44), $category, count($photos), $fellBack ? '  ⚠ galeri yok' : ''));
                $created++;
                $bar->advance();

                continue;
            }

            // Kimlik slug degil KAYNAK ADRESI: ayni model adi iki tedarikcide
            // birden gectiginde slug eslesmesi digerinin urununu devraliyordu.
            $existing = $identity->existing($url);
            $slug = $existing?->slug ?? $identity->slug($name, $key);

            $stored = [];

            foreach (array_slice($photos, 0, self::MAX_PHOTOS) as $index => $photo) {
                $path = $images->store($photo, $key, $slug, $index);

                if ($path !== null) {
                    $stored[] = $path;
                    usleep($wait);
                }
            }

            if ($stored === []) {
                $failed[] = $name.' (görsel indirilemedi)';
                $bar->advance();

                continue;
            }

            // $slug, yeniden aktarimda mevcut urunun slug'i; yeni urunde
            // bos olan bir slug. Iki durumda da dogru kaydi hedefler.
            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $category)->value('id'),
                'brand_id' => $brand?->id,
                'name' => ['tr' => $name],
                'short_description' => ['tr' => $site['ad'].' koleksiyonundan.'],
                'description' => ['tr' => $name.', '.$site['ad'].' koleksiyonundan. Kumaş ve renk seçenekleri için bizimle iletişime geçin.'],
                'price' => 0,
                'manage_stock' => false,
                'stock' => 0,
                'cover' => $stored[0],
                'hover_image' => $stored[1] ?? null,
                'is_active' => (bool) $this->option('yayinda'),
                'published_at' => now(),
                'meta' => ['kaynak' => $site['taban'], 'kaynak_adres' => $url],
            ]);

            if (count($stored) > 2) {
                $product->images()->delete();

                foreach (array_slice($stored, 2) as $position => $path) {
                    $product->images()->create(['path' => $path, 'position' => $position]);
                }
            }

            $product->wasRecentlyCreated ? $created++ : $updated++;

            unset($product, $page, $photos);
            gc_collect_cycles();

            $bar->advance();
            usleep($wait);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($dry ? '[KURU] ' : '').$created.' ürün eklendi, '.$updated.' ürün güncellendi.');

        if ($failed !== []) {
            $this->warn(count($failed).' sayfa atlandı: '.implode(', ', array_slice($failed, 0, 6)).(count($failed) > 6 ? '…' : ''));
        }

        // Galerisi isaretlenmemis sayfada fotograflar sayfa taramasindan geldi;
        // baska urunun fotografi karismis olabilir, GOZLE bakilmali.
        if ($suspect !== []) {
            $this->warn(count($suspect).' ürünün galerisi işaretli değil, fotoğrafları sayfa taramasından geldi — gözle doğrulayın: '.implode(', ', array_slice($suspect, 0, 6)).(count($suspect) > 6 ? '…' : ''));
        }

        if (! $dry && ! $this->option('yayinda')) {
            $this->line('Ürünler TASLAK (pasif) olarak eklendi — yayına almak için --yayinda.');
        }

        return self::SUCCESS;
    }

    /**
     * Urun olmayan adresleri (kurumsal sayfa, dil kopyasi, besleme) atar.
     *
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    private function withoutSkipped(array $urls, array $site): array
    {
        $skip = $site['atla'] ?? [];

        return array_values(array_filter($urls, function (string $url) use ($skip) {
            $path = '/'.trim(parse_url($url, PHP_URL_PATH) ?? '', '/').'/';

            // Ingilizce kopyalar ayni urunun ikinci kaydi olur.
            if (str_starts_with($path, '/en/')) {
                return false;
            }

            // Kategori sayfasindaki baglantilar arasinda dosya, besleme ve
            // API adresleri de var; urun sayfasi olmadiklari belli.
            if (preg_match('#\.(png|jpe?g|webp|svg|gif|pdf|php|xml|css|js)/?$|/wp-json/|/feed/|/comment-page-#i', $path)) {
                return false;
            }

            // /893/ gibi yalnizca sayidan olusan adresler ek/yorum baglantisi.
            if (preg_match('#^/\d+/$#', $path)) {
                return false;
            }

            return ! in_array($path, $skip, true);
        }));
    }

    /** @return array<int, string> */
    private function productUrls(RemoteFetcher $fetcher, array $site): array
    {
        $response = $fetcher->xml($site['site_haritasi']);

        if (! $response['ok']) {
            $this->error('Site haritası alınamadı: '.($response['error'] ?? ''));

            return [];
        }

        preg_match_all('#<loc>([^<]+)</loc>#', $response['body'], $m);

        $urls = [];

        foreach ($m[1] as $url) {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $path = parse_url($url, PHP_URL_PATH) ?? '';

            if (in_array($path, $site['atla'] ?? [], true)) {
                continue;
            }

            $urls[$url] = true;
        }

        return array_keys($urls);
    }

    /**
     * Kategori sayfalarini gezip "urun adresi => Sevkur kategorisi" haritasi kurar.
     *
     * @return array<string, string>
     */
    private function categoryMap(RemoteFetcher $fetcher, array $site, int $wait): array
    {
        $base = rtrim($site['taban'], '/');
        $map = [];

        foreach ($site['kategoriler'] ?? [] as $path => $category) {
            foreach ($this->categoryPages($fetcher, $base, $path, $wait) as $html) {
                preg_match_all('#href="('.preg_quote($base, '#').'/[^"?]+)"#', $html, $m);

                foreach ($m[1] as $url) {
                    // Ilk kategori kazanir: ayarda once yazilan daha ozel olandir.
                    $map[rtrim($url, '/')] ??= $category;
                }
            }
        }

        return $map;
    }

    /**
     * Bir kategorinin BUTUN sayfalari.
     *
     * Kategoriler sayfalanmis: yalnizca ilk sayfa okundugunda Sude'nin alti
     * kose koltugu haritaya hic girmemis ve varsayilan kategoriye dusmustu.
     * Sayfa 2'den itibaren, urun baglantisi kalmayana kadar yuruyoruz.
     *
     * @return iterable<int, string>
     */
    private function categoryPages(RemoteFetcher $fetcher, string $base, string $path, int $wait): iterable
    {
        $path = '/'.trim($path, '/').'/';

        for ($page = 1; $page <= self::MAX_CATEGORY_PAGES; $page++) {
            $url = $page === 1 ? $base.$path : $base.$path.'page/'.$page.'/';
            $response = $fetcher->html($url);

            if (! $response['ok']) {
                if ($page === 1) {
                    $this->warn("Kategori sayfası alınamadı: $path");
                }

                return;
            }

            yield $response['body'];

            usleep($wait);

            // Sonraki sayfaya bag yoksa bitti.
            if (! str_contains($response['body'], trim($path, '/').'/page/'.($page + 1))) {
                return;
            }
        }
    }

    private function category(string $url, array $site, array $map): string
    {
        return (new CategoryFromPath)($url, $site)
            ?? $map[rtrim($url, '/')]
            ?? $site['varsayilan'];
    }

    /**
     * URUN GALERISINDEKI tam boyut fotograflar.
     *
     * Sayfanin tamamini taramak ise yaramaz: Sude urun sayfalarinda ilgili
     * urunler ve slider yuzunden 130 kadar gorsel var, ilk dordunu almak
     * BASKA urunun fotografini kapak yapmak demekti. WooCommerce galeriyi
     * `data-large_image` ile ayrica isaretliyor — dogru kaynak o.
     *
     * Galeri bulunamazsa sayfa taramasina dusuluyor; o durumda sonuc
     * supheli sayilip cagiran tarafa bildiriliyor ($fellBack).
     *
     * @return array<int, string>
     */
    private function galleryImages(string $html, array $site, ?bool &$fellBack = null): array
    {
        $fellBack = false;

        // 1) Galeri kabi ayarda tanimliysa ONCE onu kes. Ravizza'da urun
        //    sayfasinin altinda ilgili urun karuseli var; kesilmeden
        //    Theraphy sayfasindan Bambu ve Bohem fotograflari geliyordu.
        if (isset($site['galeri_basi'])) {
            $html = $this->slice($html, $site['galeri_basi'], $site['galeri_sonu'] ?? null) ?? $html;
        }

        // 2) WooCommerce galeriyi ayrica isaretliyor — en guvenilir kaynak.
        if (preg_match_all('#data-large_image="([^"]+)"#i', $html, $m) && $m[1] !== []) {
            return $this->sameFamily($this->uniqueImages($m[1]), $site);
        }

        $host = preg_quote(parse_url($site['taban'], PHP_URL_HOST), '#');

        preg_match_all(
            '#https?://'.$host.'/wp-content/uploads/[^"\'?\s\\\\]+\.(?:webp|jpg|jpeg|png)#i',
            $html,
            $m
        );

        // Kap kesilmediyse sonuc sayfanin tamamindan geldi: supheli.
        $fellBack = ! isset($site['galeri_basi']);

        return $this->sameFamily($this->uniqueImages($m[0]), $site);
    }

    /** Iki isaret arasindaki HTML parcasi; bas isaret yoksa null. */
    private function slice(string $html, string $start, ?string $end): ?string
    {
        $from = strpos($html, $start);

        if ($from === false) {
            return null;
        }

        if ($end === null) {
            return substr($html, $from);
        }

        $to = strpos($html, $end, $from + strlen($start));

        return $to === false
            ? substr($html, $from)
            : substr($html, $from, $to - $from);
    }

    /**
     * Yalnizca ILK fotografla ayni dosya ailesinden olanlar.
     *
     * Ravizza'nin galerisi "theraphy-yatak-baza-seti-1.jpg … -7.jpg" seklinde
     * tek aile; araya baska urun karisirsa dosya adi gorunur sekilde ayrisir.
     * Sude'de ise bir urunun fotograflari "oscar.webp" ve
     * "oscar-koltuk-takimi-10.webp" gibi iki aileye yayilmis — bu yuzden kural
     * site basina acilir.
     *
     * @param  array<int, string>  $images
     * @return array<int, string>
     */
    private function sameFamily(array $images, array $site): array
    {
        if (empty($site['ayni_aile']) || $images === []) {
            return $images;
        }

        $family = $this->family($images[0]);

        return array_values(array_filter(
            $images,
            fn (string $url) => $this->family($url) === $family
        ));
    }

    /** Dosya adinin sondaki sira numarasi atilmis hali. */
    private function family(string $url): string
    {
        $name = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_FILENAME);

        return preg_replace('/-\d+$/', '', $name);
    }

    /**
     * Olculu kopyalari ve arayuz gorsellerini atip tekilleştirir.
     *
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    private function uniqueImages(array $urls): array
    {
        $images = [];

        foreach ($urls as $url) {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // -800x800.jpg gibi olculu kopyalar vitrinde bulanik kalir.
            if (preg_match('#-\d{2,4}x\d{2,4}\.|logo|icon|favicon|placeholder|cropped-#i', $url)) {
                continue;
            }

            $images[$url] = true;
        }

        return array_keys($images);
    }

    private function label(string $url): string
    {
        return basename(rtrim($url, '/'));
    }

    private function brand(string $key, array $site): Brand
    {
        return Brand::updateOrCreate(['slug' => $key], [
            'name' => array_fill_keys(['tr', 'en', 'bg', 'el'], $site['ad']),
            'is_active' => true,
            'position' => 20,
        ]);
    }
}
