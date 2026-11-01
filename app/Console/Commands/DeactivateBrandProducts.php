<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Bir markanin urunlerini yayindan alir (veya geri yayina koyar).
 *
 * Tedarikci sitelerinden aktarilan urunlerin fotograflari o markalarin
 * telifli urun cekimleridir. Bayilik veya yazili izin yoksa magazada yayinda
 * durmamalari gerekir; bu komut urunleri SILMEDEN gizler, boylece izin
 * geldiginde --geri ile tek komutta yayina donerler.
 *
 * Katalog PDF'inden gelen markalar (Oben, Omairat) baska durumda: onlari
 * kullanici tedarikciden aldigi dagitim materyali olarak verdi.
 */
class DeactivateBrandProducts extends Command
{
    protected $signature = 'marka:pasif
        {marka* : Marka slug(ları) — birden fazla verilebilir}
        {--geri : Tersini yap — ürünleri yeniden yayına al}';

    protected $description = 'Bir markanın ürünlerini yayından alır (telif izni netleşene kadar)';

    public function handle(): int
    {
        $publish = (bool) $this->option('geri');
        $touched = 0;

        foreach ($this->argument('marka') as $slug) {
            $brand = Brand::where('slug', $slug)->first();

            if ($brand === null) {
                $this->warn("Marka bulunamadı: $slug");

                continue;
            }

            $affected = Product::where('brand_id', $brand->id)
                ->where('is_active', ! $publish)
                ->update(['is_active' => $publish]);

            // Marka sayfasi da ayni durumu izler: pasif urunlerin markasi
            // menude bos bir kategori gibi gorunmesin.
            $brand->update(['is_active' => $publish]);

            $this->line(sprintf(
                '  %-14s %3d ürün %s',
                $brand->t('name'),
                $affected,
                $publish ? 'yayına alındı' : 'yayından alındı'
            ));

            $touched += $affected;
        }

        $this->info($touched.' ürün '.($publish ? 'yeniden yayına alındı' : 'yayından alındı').'.');

        return self::SUCCESS;
    }
}
