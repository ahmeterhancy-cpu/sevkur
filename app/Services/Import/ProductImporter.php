<?php

namespace App\Services\Import;

use App\Models\Product;
use App\Support\Locales;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Bir urun sayfasindan ad, aciklama, fiyat ve gorselleri okur.
 *
 * Kaynak sirasi (guvenilirden zayifa):
 *   1. JSON-LD  (schema.org/Product) — en saglam, cogu magaza yaziyor
 *   2. Acik grafik / meta etiketleri (og:title, product:price:amount…)
 *   3. WooCommerce sinif adlari — referans tema WooCommerce
 *   4. Genel yedekler (h1, ilk buyuk gorsel)
 *
 * Her kaynak yalniz BOS alanlari doldurur; ustteki kazanir.
 */
class ProductImporter
{
    private const MAX_IMAGES = 12;

    public function __construct(private readonly RemoteFetcher $fetcher) {}

    public function preview(string $url): ImportedProduct
    {
        $result = $this->fetcher->html($url);

        if (! $result['ok']) {
            return new ImportedProduct(sourceUrl: $url, notes: [$result['error'] ?? 'Sayfa alınamadı.']);
        }

        $product = new ImportedProduct(sourceUrl: $result['url']);

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$result['body']);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        $this->fromJsonLd($xpath, $product);
        $this->fromMeta($xpath, $product);
        $this->fromWooCommerce($xpath, $product);
        $this->fromGeneric($xpath, $product);

        $product->images = $this->normaliseImages($product->images, $product->sourceUrl);

        if (! $product->hasEnough()) {
            $product->notes[] = 'Sayfada ürün adı bulunamadı. Adres bir ürün sayfası olmayabilir.';
        }

        if ($product->images === []) {
            $product->notes[] = 'Görsel bulunamadı; kaydettikten sonra elle yükleyebilirsiniz.';
        }

        if ($product->currency !== null && strtoupper($product->currency) !== 'TRY') {
            $product->notes[] = 'Fiyat '.strtoupper($product->currency).' olarak okundu. Mağaza TRY ile çalışır — kaydetmeden önce tutarı çevirin.';
        }

        return $product;
    }

    // ------------------------------------------------------------------ kaynaklar

    private function fromJsonLd(DOMXPath $xpath, ImportedProduct $product): void
    {
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $node) {
            $decoded = json_decode(trim($node->textContent), true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($this->flattenGraph($decoded) as $entry) {
                if (! $this->isProductNode($entry)) {
                    continue;
                }

                $this->fill($product, 'name', $this->text($entry['name'] ?? null));
                $this->fill($product, 'description', $this->text($entry['description'] ?? null));
                $this->fill($product, 'sku', $this->text($entry['sku'] ?? $entry['mpn'] ?? null));

                $brand = $entry['brand'] ?? null;
                $this->fill($product, 'brand', $this->text(is_array($brand) ? ($brand['name'] ?? null) : $brand));

                $offer = $entry['offers'] ?? null;
                $offer = is_array($offer) && array_is_list($offer) ? ($offer[0] ?? null) : $offer;

                if (is_array($offer)) {
                    $this->fill($product, 'price', $this->number($offer['price'] ?? $offer['lowPrice'] ?? null));
                    $this->fill($product, 'currency', $this->text($offer['priceCurrency'] ?? null));
                }

                foreach ((array) ($entry['image'] ?? []) as $image) {
                    $value = is_array($image) ? ($image['url'] ?? null) : $image;

                    if (is_string($value)) {
                        $product->images[] = $value;
                    }
                }
            }
        }
    }

    private function fromMeta(DOMXPath $xpath, ImportedProduct $product): void
    {
        $meta = function (string $property) use ($xpath): ?string {
            $node = $xpath->query(sprintf(
                '//meta[@property="%1$s" or @name="%1$s"]/@content',
                $property
            ))?->item(0);

            return $node ? trim($node->nodeValue) : null;
        };

        $this->fill($product, 'name', $meta('og:title'));
        $this->fill($product, 'shortDescription', $meta('og:description') ?? $meta('description'));
        $this->fill($product, 'price', $this->number($meta('product:price:amount') ?? $meta('og:price:amount')));
        $this->fill($product, 'currency', $meta('product:price:currency') ?? $meta('og:price:currency'));

        if ($image = $meta('og:image')) {
            $product->images[] = $image;
        }
    }

    private function fromWooCommerce(DOMXPath $xpath, ImportedProduct $product): void
    {
        $first = function (string $query) use ($xpath): ?string {
            $node = $xpath->query($query)?->item(0);

            return $node ? trim(preg_replace('/\s+/u', ' ', $node->textContent)) : null;
        };

        $this->fill($product, 'name', $first('//*[contains(@class,"product_title")]'));
        $this->fill($product, 'shortDescription', $first('//*[contains(@class,"woocommerce-product-details__short-description")]'));
        $this->fill($product, 'description', $first('//*[@id="tab-description"]'));
        $this->fill($product, 'sku', $first('//*[contains(@class,"sku")]'));

        // Fiyat: indirimliyse <ins> guncel, <del> ustu cizili fiyattir.
        // JSON-LD genelde YALNIZCA guncel fiyati verir, bu yuzden ustu cizili
        // fiyat her zaman isaretlemeden okunur.
        $current = $first('//*[contains(@class,"price")]//ins//*[contains(@class,"amount")]')
            ?? $first('//*[contains(@class,"summary")]//*[contains(@class,"amount")]');
        $previous = $first('//*[contains(@class,"price")]//del//*[contains(@class,"amount")]')
            ?? $first('//del//*[contains(@class,"amount")]');

        $this->fill($product, 'price', $this->number($current));
        $this->fill($product, 'compareAtPrice', $this->number($previous));

        foreach ($xpath->query('//*[contains(@class,"woocommerce-product-gallery")]//img') ?: [] as $img) {
            foreach (['data-large_image', 'data-src', 'src'] as $attribute) {
                $value = $img->getAttribute($attribute);

                if ($value !== '') {
                    $product->images[] = $value;
                    break;
                }
            }
        }
    }

    private function fromGeneric(DOMXPath $xpath, ImportedProduct $product): void
    {
        if ($product->name === null) {
            $node = $xpath->query('//h1')?->item(0);
            $this->fill($product, 'name', $node ? trim($node->textContent) : null);
        }

        if ($product->images === []) {
            foreach ($xpath->query('//img/@src') ?: [] as $node) {
                $product->images[] = $node->nodeValue;

                if (count($product->images) >= self::MAX_IMAGES) {
                    break;
                }
            }
        }
    }

    // ------------------------------------------------------------------ yardimci

    /** JSON-LD @graph ve listeleri duz bir diziye acar. */
    private function flattenGraph(array $data): array
    {
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            return $data['@graph'];
        }

        return array_is_list($data) ? $data : [$data];
    }

    private function isProductNode(mixed $entry): bool
    {
        if (! is_array($entry)) {
            return false;
        }

        $types = (array) ($entry['@type'] ?? []);

        foreach ($types as $type) {
            if (is_string($type) && str_contains(strtolower($type), 'product')) {
                return true;
            }
        }

        return false;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $clean === '' ? null : $clean;
    }

    /** "1.299,00 ₺" / "$1,299.00" gibi yazimlari sayiya cevirir. */
    private function number(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        if (! preg_match('/-?[\d.,\s]{1,20}/', $value, $match)) {
            return null;
        }

        $raw = preg_replace('/\s/', '', $match[0]);

        if ($raw === '' || ! preg_match('/\d/', $raw)) {
            return null;
        }

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Sondaki ayirici ondaliktir
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousand = $decimal === ',' ? '.' : ',';
            $raw = str_replace($thousand, '', $raw);
            $raw = str_replace($decimal, '.', $raw);
        } elseif ($lastComma !== false) {
            // Tek virgul: ondalik mi binlik mi? Son parca 3 haneliyse binlik say
            $tail = substr($raw, $lastComma + 1);
            $raw = strlen($tail) === 3 ? str_replace(',', '', $raw) : str_replace(',', '.', $raw);
        } elseif ($lastDot !== false) {
            $tail = substr($raw, $lastDot + 1);
            $raw = strlen($tail) === 3 ? str_replace('.', '', $raw) : $raw;
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function fill(ImportedProduct $product, string $field, mixed $value): void
    {
        if ($value === null || $value === '' || $product->{$field} !== null) {
            return;
        }

        $product->{$field} = $value;
    }

    /** @param array<int, string> $images @return array<int, string> */
    private function normaliseImages(array $images, string $base): array
    {
        $clean = [];

        foreach ($images as $image) {
            if (! is_string($image) || trim($image) === '') {
                continue;
            }

            $absolute = $this->fetcher->absolute(trim($image), $base);

            // Izleyici pikselleri, ikonlar ve veri adresleri
            if (str_starts_with($absolute, 'data:')) {
                continue;
            }

            if (preg_match('/(sprite|icon|logo|placeholder|pixel|blank)\b/i', $absolute)) {
                continue;
            }

            $clean[$absolute] = true;

            if (count($clean) >= self::MAX_IMAGES) {
                break;
            }
        }

        return array_keys($clean);
    }

    // --------------------------------------------------------------- kaydetme

    /**
     * Formdan gelen (yoneticinin duzenledigi) veriyi urune cevirir.
     *
     * Urun PASIF olarak olusturulur: yonetici fiyati, ceviriyi ve kategoriyi
     * kontrol edip kendisi yayina alir.
     *
     * @param  array<string, mixed>  $data
     * @return array{product: Product, downloaded: int, failed: int}
     */
    public function create(array $data): array
    {
        $locale = Locales::default();
        $name = trim((string) ($data['name'] ?? ''));

        $slug = Str::slug($name);

        if ($slug === '' || Product::where('slug', $slug)->exists()) {
            $slug = ($slug !== '' ? $slug : 'urun').'-'.Str::lower(Str::random(5));
        }

        $product = Product::create([
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'name' => [$locale => $name],
            'slug' => $slug,
            'sku' => ($data['sku'] ?? null) ?: null,
            'short_description' => [$locale => (string) ($data['short_description'] ?? '')],
            'description' => [$locale => (string) ($data['description'] ?? '')],
            'price' => (float) ($data['price'] ?? 0),
            'compare_at_price' => ($data['compare_at_price'] ?? null) !== null && $data['compare_at_price'] !== ''
                ? (float) $data['compare_at_price']
                : null,
            'stock' => (int) ($data['stock'] ?? 0),
            'manage_stock' => true,
            'is_active' => false,
            'meta' => ['import_source' => $data['source_url'] ?? null],
            'published_at' => null,
        ]);

        $downloaded = 0;
        $failed = 0;

        foreach ((array) ($data['selected_images'] ?? []) as $index => $url) {
            $path = $this->storeImage((string) $url, $slug, (int) $index);

            if ($path === null) {
                $failed++;

                continue;
            }

            if ($downloaded === 0) {
                $product->update(['cover' => $path]);
            } elseif ($downloaded === 1) {
                $product->update(['hover_image' => $path]);
            } else {
                $product->images()->create(['path' => $path, 'position' => $downloaded]);
            }

            $downloaded++;
        }

        return ['product' => $product->fresh(), 'downloaded' => $downloaded, 'failed' => $failed];
    }

    /** Gorseli indirir, storage/app/public/urunler altina yazar. */
    private function storeImage(string $url, string $slug, int $index): ?string
    {
        $result = $this->fetcher->image($url);

        if (! $result['ok'] || $result['body'] === null) {
            return null;
        }

        $image = @imagecreatefromstring($result['body']);

        if (! $image) {
            return null;
        }

        // Cok buyuk gorselleri kucult
        $width = imagesx($image);
        $height = imagesy($image);
        $max = 1400;

        if ($width > $max || $height > $max) {
            $scale = $max / max($width, $height);
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));

            if ($resized !== false) {
                $image = $resized;
            }
        }

        $relative = 'urunler/'.$slug.'-'.$index.'.jpg';
        // 'medya' diski public/ kokunu kullanir; panel ve magaza ayni yerden okur
        $absolute = public_path($relative);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        return imagejpeg($image, $absolute, 85) ? $relative : null;
    }
}
