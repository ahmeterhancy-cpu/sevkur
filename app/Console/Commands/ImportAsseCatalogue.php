<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Import\ProductIdentity;
use Illuminate\Console\Command;

/**
 * Asse Koltuk 2026 katalogunu magazaya aktarir.
 *
 * assekoltuk.com tek sayfalik bir site: urun sayfasi yok, butun katalog
 * asse-2026.pdf dosyasinda. Katalogun ikinci sayfasi DIZIN — model adlari ve
 * basili sayfa araliklari orada; her model kendi sayfalarinin metninde adi
 * gecerek dogrulandi (bkz. tools/asse-veri.php).
 *
 * Veri storage/app/asse-urunler.json dosyasindan okunur.
 */
class ImportAsseCatalogue extends Command
{
    protected $signature = 'asse:aktar
        {--veri=asse-urunler.json : storage/app altındaki JSON}
        {--yayinda : Ürünleri yayına al (varsayılan: taslak)}
        {--kuru : Hiçbir şey yazma, ne yapılacağını göster}';

    protected $description = 'Asse Koltuk 2026 kataloğundaki ürünleri ekler';

    public function handle(ProductIdentity $identity): int
    {
        $path = storage_path('app/'.$this->option('veri'));

        if (! is_file($path)) {
            $this->error("Veri dosyası yok: $path");

            return self::FAILURE;
        }

        $items = json_decode(file_get_contents($path), true);

        if (! is_array($items)) {
            $this->error('Veri dosyası okunamadı.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('kuru');
        $brand = $dry ? null : $this->brand();

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($items as $item) {
            $images = array_values(array_filter($item['gorseller'] ?? [], 'is_file'));

            if ($images === []) {
                $skipped[] = $item['ad'].' (fotoğraf yok)';

                continue;
            }

            if ($dry) {
                $this->line(sprintf(
                    '  %-26s %-16s s.%-8s %d görsel',
                    $item['ad'],
                    $item['kategori'],
                    $item['basili_sayfa'],
                    count($images)
                ));

                $created++;

                continue;
            }

            // Kimlik katalog sayfasi: ayni model adi baska tedarikcide de var.
            $source = 'asse-2026.pdf#'.$item['basili_sayfa'];
            $existing = $identity->existing($source);
            $slug = $existing?->slug ?? $identity->slug($item['ad'], 'asse');

            $stored = [];

            foreach ($images as $index => $file) {
                $target = $this->storeImage($file, $slug, $index);

                if ($target !== null) {
                    $stored[] = $target;
                }
            }

            if ($stored === []) {
                $skipped[] = $item['ad'].' (fotoğraf yazılamadı)';

                continue;
            }

            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $item['kategori'])->value('id'),
                'brand_id' => $brand?->id,
                'name' => ['tr' => $item['ad']],
                'short_description' => ['tr' => 'Asse Koltuk 2026 koleksiyonundan.'],
                'description' => ['tr' => $this->description($item)],
                'specs' => $item['specs'] ?: null,
                'price' => 0,
                'manage_stock' => false,
                'stock' => 0,
                'cover' => $stored[0],
                'hover_image' => $stored[1] ?? null,
                'is_active' => (bool) $this->option('yayinda'),
                'published_at' => now(),
                'meta' => [
                    'kaynak' => 'Asse Koltuk 2026 Kataloğu',
                    'kaynak_adres' => $source,
                    'katalog_sayfa' => $item['basili_sayfa'],
                ],
            ]);

            if (count($stored) > 2) {
                $product->images()->delete();

                foreach (array_slice($stored, 2) as $position => $imagePath) {
                    $product->images()->create(['path' => $imagePath, 'position' => $position]);
                }
            }

            $product->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->newLine();
        $this->info(($dry ? '[KURU] ' : '').$created.' ürün eklendi, '.$updated.' ürün güncellendi.');

        if ($skipped !== []) {
            $this->warn('Atlananlar: '.implode(', ', $skipped));
        }

        if (! $dry && ! $this->option('yayinda')) {
            $this->line('Ürünler TASLAK (pasif) olarak eklendi — yayına almak için --yayinda.');
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $item */
    private function description(array $item): string
    {
        $lines = [
            $item['ad'].', Asse Koltuk 2026 koleksiyonundan.',
            '',
        ];

        foreach ($item['specs'] as $label => $value) {
            $lines[] = '· '.$label.': '.$value;
        }

        $lines[] = '';
        $lines[] = 'Kumaş ve renk seçenekleri için bizimle iletişime geçin.';

        return implode("\n", $lines);
    }

    private function storeImage(string $source, string $slug, int $index): ?string
    {
        $relative = sprintf('urunler/asse/%s-%d.jpg', $slug, $index);
        $target = public_path($relative);

        if (is_file($target)) {
            return $relative;
        }

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        $image = @imagecreatefromjpeg($source);

        if ($image === false) {
            return null;
        }

        if (imagesx($image) > 1400) {
            $scaled = imagescale($image, 1400);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        $ok = imagejpeg($image, $target, 86);
        imagedestroy($image);

        return $ok && is_file($target) && filesize($target) > 1024 ? $relative : null;
    }

    private function brand(): Brand
    {
        return Brand::updateOrCreate(['slug' => 'asse'], [
            'name' => array_fill_keys(['tr', 'en', 'bg', 'el'], 'Asse Koltuk'),
            'is_active' => true,
            'position' => 21,
        ]);
    }
}
