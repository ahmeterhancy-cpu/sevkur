<?php

namespace Tests;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\ContentSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShopSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Magaza testleri icin ortak taban: kategori/urun/kargo verisi hazir gelir.
 * Tam demo icerigi (blog, afis, gorseller) yerine hizli bir alt kume seeder'i
 * kullanilir; gorsel uretimi testleri yavaslatmasin diye.
 */
abstract class ShopTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SettingsSeeder::class,
            CurrencySeeder::class,
            TaxonomySeeder::class,
        ]);

        $this->seedProducts();

        $this->seed([
            ContentSeeder::class,
            ShopSeeder::class,
        ]);
    }

    /** Testler icin hafif urun kumesi (gorsel uretmez). */
    protected function seedProducts(): void
    {
        $category = Category::where('slug', 'koltuk-takimlari')->firstOrFail();
        $brand = Brand::first();

        foreach (range(1, 5) as $i) {
            $product = Product::create([
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'name' => ['tr' => "Test Koltuk $i", 'en' => "Test Sofa $i", 'bg' => "Тест диван $i", 'el' => "Δοκιμή καναπές $i"],
                'slug' => "test-koltuk-$i",
                'sku' => "TST-000$i",
                'short_description' => ['tr' => 'Kısa açıklama', 'en' => 'Short description'],
                'description' => ['tr' => 'Uzun açıklama metni.', 'en' => 'Long description text.'],
                'price' => 1000 * $i,
                'compare_at_price' => $i === 1 ? 1500 : null,
                'stock' => 10,
                'manage_stock' => true,
                'delivery_days' => 3,
                'is_active' => true,
                'is_featured' => $i <= 2,
                'position' => $i,
                'published_at' => now()->subDay(),
            ]);

            if ($i === 5) {
                $product->variants()->create([
                    'name' => ['tr' => 'Ceviz', 'en' => 'Walnut'],
                    'sku' => 'TST-0005-CEV',
                    'stock' => 4,
                    'position' => 0,
                ]);
            }
        }
    }

    protected function product(int $index = 1): Product
    {
        return Product::where('slug', "test-koltuk-$index")->firstOrFail();
    }
}
