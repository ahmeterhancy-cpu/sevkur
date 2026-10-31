<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Omairat (Akdoğan Mobilya) 2025-26 katalogunu magazaya aktarir.
 *
 * Katalog KOD bazlidir (2081 konsol, 7081 masa, 8055 sandalye…) ve bir
 * sayfada iki ile on yedi arasinda kod bulunur. Kod basina ayri urun acmak
 * denendi; sayfa duzenleri degisken oldugu icin fotograf-kod eslesmesi
 * kayitlarin ancak yarisinda guvenilirdi. Yanlis fotografli iki yuz urun,
 * dogru fotografli elli urunden kotu oldugu icin her SAYFA bir urun olarak
 * aktariliyor: sayfadaki butun kodlar, agirliklar ve olculer teknik
 * ozelliklere yaziliyor, musteri koduyla arayabiliyor.
 *
 * Veri storage/app/omairat-urunler.json dosyasindan okunur; o dosya katalog
 * PDF'inden tools/ altindaki betiklerle uretilir.
 */
class ImportOmairatCatalogue extends Command
{
    protected $signature = 'omairat:aktar
        {--veri=omairat-urunler.json : storage/app altindaki JSON}
        {--kuru : Hicbir sey yazma, ne yapilacagini goster}';

    protected $description = 'Omairat 2025-26 katalogundaki ürünleri taslak olarak ekler';

    /** Kod on ekine gore urun turu — teknik ozellik tablosunda kullanilir. */
    private const CODE_TYPES = [
        '1' => 'Vitrin',
        '2' => 'Konsol',
        '3' => 'Tv Sehpası',
        '4' => 'Orta Sehpa',
        '5' => 'Zigon Sehpa',
        '7' => 'Masa',
        '8' => 'Sandalye',
    ];

    public function handle(): int
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
            $images = array_values(array_filter(
                $item['gorseller'] ?? [],
                fn (string $p) => is_file($p)
            ));

            if ($images === []) {
                $skipped[] = $item['ad'];

                continue;
            }

            $slug = Str::slug($item['ad']);

            if ($dry) {
                $this->line(sprintf(
                    '  %-38s %-24s %2d kod, %d görsel',
                    $item['ad'],
                    $item['kategori'],
                    count($item['kodlar']),
                    count($images)
                ));

                $created++;

                continue;
            }

            $stored = [];

            foreach ($images as $index => $source) {
                $target = $this->storeImage($source, $slug, $index);

                if ($target !== null) {
                    $stored[] = $target;
                }
            }

            if ($stored === []) {
                $skipped[] = $item['ad'];

                continue;
            }

            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $item['kategori'])->value('id'),
                'brand_id' => $brand?->id,
                'name' => ['tr' => $item['ad']],
                'sku' => $item['kodlar'][0] ?? null,
                'short_description' => ['tr' => $this->summary($item)],
                'description' => ['tr' => $this->description($item)],
                'specs' => $item['specs'] ?: null,
                'price' => 0,
                'manage_stock' => false,
                'stock' => 0,
                'cover' => $stored[0],
                'hover_image' => $stored[1] ?? null,
                'is_active' => true,
                'published_at' => now(),
                'meta' => [
                    'kaynak' => 'Omairat 2025-26 Kataloğu',
                    'katalog_sayfa' => $item['sayfa'],
                    'kodlar' => $item['kodlar'],
                ],
            ]);

            $product->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->newLine();
        $this->info(($dry ? '[KURU] ' : '').$created.' ürün eklendi, '.$updated.' ürün güncellendi.');

        if ($skipped !== []) {
            $this->warn('Görseli bulunamayanlar: '.implode(', ', $skipped));
        }

        if (! $dry) {
            $this->line('Ürünler yayında ama fiyatsız — fiyat girilene kadar sepete eklenemezler.');
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $item */
    private function summary(array $item): string
    {
        $count = count($item['kodlar']);

        return $count > 1
            ? $count.' parça kod seçeneğiyle Omairat koleksiyonundan.'
            : 'Omairat koleksiyonundan.';
    }

    /** Urun aciklamasi: kodlar turleriyle birlikte listelenir. */
    private function description(array $item): string
    {
        $lines = [
            'Omairat (Akdoğan Mobilya) 2025-26 koleksiyonundan '.mb_strtolower($item['ad']).'.',
            '',
            'Bu sayfadaki ürün kodları:',
        ];

        foreach ($item['specs'] as $label => $detail) {
            $code = trim(str_replace('Kod', '', $label));
            $type = self::CODE_TYPES[$code[0] ?? ''] ?? 'Ürün';

            $lines[] = '· '.$type.' — Kod '.$code.($detail !== '—' ? ' ('.$detail.')' : '');
        }

        $lines[] = '';
        $lines[] = 'Sipariş verirken ürün kodunu belirtin. Sandalye siparişlerinde kumaş ve cila rengini bildirin.';

        return implode("\n", $lines);
    }

    private function storeImage(string $source, string $slug, int $index): ?string
    {
        $relative = sprintf('urunler/omairat/%s-%d.jpg', $slug, $index);
        $target = public_path($relative);

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

        return $ok ? $relative : null;
    }

    private function brand(): Brand
    {
        return Brand::updateOrCreate(['slug' => 'omairat'], [
            'name' => ['tr' => 'Omairat', 'en' => 'Omairat', 'bg' => 'Omairat', 'el' => 'Omairat'],
            'is_active' => true,
            'position' => 11,
        ]);
    }
}
