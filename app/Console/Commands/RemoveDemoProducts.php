<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kurulumla gelen demo urunlerini ve uydurma markalarini siler.
 *
 * Demo urunlerin fotograflari Wikimedia Commons'tan gelir ve GOSTERILEN
 * MOBILYALAR satilan urunler degildir — katalog gercek urunlerle dolduktan
 * sonra bunlarin magazada durmasi musteriyi yaniltir.
 *
 * Demo urunler uc isaretten taninir: uydurma markalardan birine bagli
 * olmak (Sevkur Atolye, Meric Design, Trakya Ahsap, Kaleici Koleksiyon,
 * Selimiye Home, Enez Living), fotografi public/demo altinda durmak, ya da
 * URL iceri aktaricisi denenirken Furnob tema demosundan cekilmis olmak.
 * Markaya bakmak tek basina yetmez: markasiz kalmis demo urunler de var.
 *
 * Varsayilan olarak YALNIZCA veritabani kayitlari silinir, fotograflar
 * kalir: public/demo/urunler ayni zamanda ProductSeeder'in ve panel disk
 * testlerinin duzenegidir — silinirse tohumlanan urunler kapaksiz kalir ve
 * o testler kirilir. Canliya cikmadan once dosyalari da atmak icin
 * --gorseller verilir.
 *
 * Afis, slider ve blog yazilarinin demo gorselleri (public/demo/afis-*,
 * yazi-*, sahneler/) her durumda yerinde kalir; onlar yonetimden ayri ayri
 * degistiriliyor.
 *
 * ProductSeeder'a dokunulmaz — testler onu kullaniyor. `migrate:fresh --seed`
 * demo urunleri geri getirir; o durumda bu komut yeniden calistirilir.
 */
class RemoveDemoProducts extends Command
{
    protected $signature = 'demo:temizle
        {--gorseller : public/demo/urunler fotoğraflarını da sil}
        {--kuru : Hiçbir şey silme, ne silineceğini göster}';

    protected $description = 'Kurulumla gelen demo ürünlerini ve markalarını siler';

    /** Kurulumda uretilen uydurma marka slug\'lari. */
    private const DEMO_BRANDS = [
        'sevkur-atolye', 'meric-design', 'trakya-ahsap',
        'kaleici-koleksiyon', 'selimiye-home', 'enez-living',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('kuru');

        $brandIds = Brand::whereIn('slug', self::DEMO_BRANDS)->pluck('id');

        $products = Product::query()
            ->where(function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds)
                    ->orWhere('cover', 'like', 'demo/%')
                    ->orWhere('meta->import_source', 'like', '%klbtheme.com%');
            })
            ->get(['id', 'slug', 'cover', 'brand_id']);

        if ($products->isEmpty()) {
            $this->info('Silinecek demo ürün yok.');

            return self::SUCCESS;
        }

        $this->warn($products->count().' demo ürün bulundu.');

        // Siparisi olan bir urun silinirse siparis satiri da gider; demo
        // urun satilmis olmamali ama once bakilir, varsayilmaz.
        $sold = DB::table('order_items')->whereIn('product_id', $products->pluck('id'))->count();

        if ($sold > 0) {
            $this->error("Bu ürünlerin $sold sipariş satırı var — silmek sipariş geçmişini bozar. Durduruldu.");

            return self::FAILURE;
        }

        if ($dry) {
            foreach ($products->take(10) as $p) {
                $this->line('  '.$p->slug);
            }

            if ($products->count() > 10) {
                $this->line('  … ve '.($products->count() - 10).' tane daha');
            }

            $this->info('[KURU] '.$products->count().' ürün ve '.$brandIds->count().' marka silinecekti.');

            if ($this->option('gorseller')) {
                $this->line('Fotoğraflar da silinecekti: '.public_path('demo/urunler'));
            }

            return self::SUCCESS;
        }

        $photos = $this->option('gorseller') ? $this->deleteProductPhotos() : null;

        // Varyant, galeri ve secenek fiyatlari veritabaninda cascade ile gider.
        Product::whereIn('id', $products->pluck('id'))->delete();
        Brand::whereIn('id', $brandIds)->delete();

        $this->info($products->count().' demo ürün silindi.');
        $this->info($brandIds->count().' demo marka silindi.');
        $photos === null
            ? $this->line('Fotoğraflar bırakıldı (tohumlayıcı ve panel testleri kullanıyor) — silmek için --gorseller.')
            : $this->info($photos.' demo ürün fotoğrafı silindi.');
        $this->line('Kalan ürün: '.Product::count());

        return self::SUCCESS;
    }

    /** public/demo/urunler klasorunu bosaltir. */
    private function deleteProductPhotos(): int
    {
        $dir = public_path('demo/urunler');

        if (! is_dir($dir)) {
            return 0;
        }

        $deleted = 0;

        foreach (glob($dir.'/*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        @rmdir($dir);

        return $deleted;
    }
}
