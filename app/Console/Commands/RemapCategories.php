<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Import\CategoryFromPath;
use Illuminate\Console\Command;

/**
 * Aktarilmis urunleri kategori ayarina gore yeniden dagitir.
 *
 * Kategori agaci sonradan degisince (ornegin Genc Odasi acilinca) urunleri
 * tek tek elden gecirmek gerekiyordu. Urunun kimligi kaynak adresi oldugu
 * icin kategori o adresten yeniden hesaplanabilir: siteye tekrar baglanmaya,
 * gorselleri yeniden indirmeye gerek yok.
 *
 *   php artisan kategori:yeniden-esle arno --kuru
 */
class RemapCategories extends Command
{
    protected $signature = 'kategori:yeniden-esle
                            {site : config/import-sites.php içindeki anahtar}
                            {--kuru : Hiçbir şeyi değiştirme, sadece raporla}';

    protected $description = 'Aktarılan ürünleri kaynak adresine göre yeniden kategorilendirir';

    public function handle(): int
    {
        $key = $this->argument('site');
        $site = config('import-sites.'.$key);

        if (! $site) {
            $this->error("Bilinmeyen site: $key");

            return self::FAILURE;
        }

        if (! isset($site['yoldan_kategori'])) {
            $this->error("$key yol tabanlı eşleme kullanmıyor; yeniden eşlenemez.");

            return self::FAILURE;
        }

        $kuru = (bool) $this->option('kuru');
        $slugToId = Category::pluck('id', 'slug')->all();
        $adlar = Category::pluck('slug', 'id')->all();
        $fromPath = new CategoryFromPath;

        $urunler = Product::where('meta->kaynak_adres', 'like', rtrim($site['taban'], '/').'%')->get();

        if ($urunler->isEmpty()) {
            $this->warn("$key için kaynak adresi kayıtlı ürün yok.");

            return self::SUCCESS;
        }

        $tasinan = [];
        $eksik = [];

        foreach ($urunler as $urun) {
            $hedef = $fromPath($urun->meta['kaynak_adres'], $site);

            if (! isset($slugToId[$hedef])) {
                $eksik[$hedef] = ($eksik[$hedef] ?? 0) + 1;

                continue;
            }

            $yeni = $slugToId[$hedef];

            if ($urun->category_id === $yeni) {
                continue;
            }

            $eski = $adlar[$urun->category_id] ?? '—';
            $tasinan[$eski.' → '.$hedef] = ($tasinan[$eski.' → '.$hedef] ?? 0) + 1;

            if (! $kuru) {
                $urun->update(['category_id' => $yeni]);
            }
        }

        foreach ($eksik as $slug => $adet) {
            $this->warn("Kategori veritabanında yok: $slug ($adet ürün yerinde bırakıldı)");
        }

        if ($tasinan === []) {
            $this->info("$key: taşınacak ürün yok, ".$urunler->count().' ürün zaten doğru kategoride.');

            return self::SUCCESS;
        }

        ksort($tasinan);

        $this->table(['Taşıma', 'Ürün'], array_map(
            fn ($yol, $adet) => [$yol, $adet],
            array_keys($tasinan),
            $tasinan
        ));

        $this->info(($kuru ? 'KURU ÇALIŞMA — ' : '').array_sum($tasinan).' ürün taşın'.($kuru ? 'acak' : 'dı').'.');

        return self::SUCCESS;
    }
}
