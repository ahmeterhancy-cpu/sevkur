<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Vespero urunlerini yayindan alir (veya geri yayina koyar).
 *
 * vespero.com'dan aktarilan urunlerin fotograflari o markanin telifli urun
 * cekimleridir. Bayilik veya yazili izin yoksa magazada yayinda durmamalari
 * gerekir; bu komut urunleri silmeden yalnizca gizler, boylece izin
 * geldiginde --geri ile tek komutta yayina donerler.
 */
class DeactivateVesperoProducts extends Command
{
    protected $signature = 'vespero:pasif
        {--geri : Tersini yap — ürünleri yeniden yayına al}';

    protected $description = 'Vespero ürünlerini yayından alır (telif izni netleşene kadar)';

    public function handle(): int
    {
        $brand = Brand::where('slug', 'vespero')->first();

        if ($brand === null) {
            $this->warn('Vespero markası yok, yapılacak bir şey kalmadı.');

            return self::SUCCESS;
        }

        $publish = (bool) $this->option('geri');

        $affected = Product::where('brand_id', $brand->id)
            ->where('is_active', ! $publish)
            ->update(['is_active' => $publish]);

        // Marka sayfasi da ayni durumu izler: pasif urunlerin markasi
        // menude bos bir kategori gibi gorunmesin.
        $brand->update(['is_active' => $publish]);

        $this->info($affected.' Vespero ürünü '.($publish ? 'yeniden yayına alındı' : 'yayından alındı').'.');

        return self::SUCCESS;
    }
}
