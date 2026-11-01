<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use Tests\ShopTestCase;

/**
 * vespero:pasif komutu.
 *
 * vespero.com'dan aktarilan urunlerin fotograflari o markanin telifli urun
 * cekimleri. Izin netlesene kadar yayindan alinabilmeli, izin gelince de
 * silinmeden geri donebilmeli.
 */
class VesperoDeactivateTest extends ShopTestCase
{
    private function vesperoProduct(): Product
    {
        $brand = Brand::create([
            'slug' => 'vespero',
            'name' => ['tr' => 'Vespero'],
            'is_active' => true,
        ]);

        $product = $this->product(1);
        $product->update(['brand_id' => $brand->id, 'is_active' => true]);

        return $product->fresh();
    }

    public function test_it_hides_vespero_products(): void
    {
        $product = $this->vesperoProduct();

        $this->artisan('vespero:pasif')->assertSuccessful();

        $this->assertFalse($product->fresh()->is_active);
        $this->assertFalse(Brand::where('slug', 'vespero')->first()->is_active);
    }

    public function test_it_does_not_delete_them(): void
    {
        $product = $this->vesperoProduct();

        $this->artisan('vespero:pasif')->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_it_leaves_other_brands_alone(): void
    {
        $this->vesperoProduct();

        $other = $this->product(2);
        $other->update(['is_active' => true]);

        $this->artisan('vespero:pasif')->assertSuccessful();

        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_geri_puts_them_back_on_sale(): void
    {
        $product = $this->vesperoProduct();

        $this->artisan('vespero:pasif')->assertSuccessful();
        $this->artisan('vespero:pasif --geri')->assertSuccessful();

        $this->assertTrue($product->fresh()->is_active);
        $this->assertTrue(Brand::where('slug', 'vespero')->first()->is_active);
    }

    public function test_it_survives_a_missing_brand(): void
    {
        $this->artisan('vespero:pasif')->assertSuccessful();
    }
}
