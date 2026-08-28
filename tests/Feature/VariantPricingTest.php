<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Varyant fiyatlamasi.
 *
 * Amac: 3 renk x 3 olcu = 9 varyant icin 9 fiyat girmemek. Secenek basina
 * fark girilir, varyant fiyati hesaplanir. Elle yazilan fiyat kurali ezer.
 */
class VariantPricingTest extends ShopTestCase
{
    private function optionProduct(): Product
    {
        return $this->product(5);
    }

    private function variant(string $sku): ProductVariant
    {
        return ProductVariant::where('sku', $sku)->firstOrFail();
    }

    public function test_without_rules_a_variant_costs_the_product_price(): void
    {
        $variant = $this->variant('TST-0005-CEV-2li');
        $variant->update(['price' => null]);

        $this->assertSame(
            (float) $variant->product->price,
            $variant->fresh()->effectivePrice()
        );
    }

    public function test_one_rule_prices_every_variant_that_uses_that_option(): void
    {
        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        // Tek kural: 3'lu olcu 900 TL fazla
        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '3lu',
            'price_delta' => 900,
        ]);

        $product->load('optionPrices');
        $base = (float) $product->price;

        foreach ($product->variants()->get() as $variant) {
            $variant->setRelation('product', $product);

            $expected = ($variant->options['olcu'] ?? null) === '3lu' ? $base + 900 : $base;

            $this->assertSame($expected, $variant->effectivePrice(), $variant->sku.' yanlis fiyatlandi');
        }
    }

    public function test_rules_on_different_axes_add_up(): void
    {
        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        $product->optionPrices()->createMany([
            ['attribute_slug' => 'olcu', 'value_slug' => '3lu', 'price_delta' => 900],
            ['attribute_slug' => 'renk', 'value_slug' => 'antrasit', 'price_delta' => 250],
        ]);

        $product->load('optionPrices');

        $variant = $this->variant('TST-0005-ANT-3lu');
        $variant->setRelation('product', $product);

        $this->assertSame((float) $product->price + 1150, $variant->effectivePrice());
    }

    public function test_a_negative_rule_discounts_the_option(): void
    {
        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '2li',
            'price_delta' => -500,
        ]);

        $product->load('optionPrices');

        $variant = $this->variant('TST-0005-CEV-2li');
        $variant->setRelation('product', $product);

        $this->assertSame((float) $product->price - 500, $variant->effectivePrice());
    }

    public function test_rules_never_produce_a_negative_price(): void
    {
        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        // Yanlislikla urun fiyatindan buyuk bir indirim girilirse
        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '2li',
            'price_delta' => -999999,
        ]);

        $product->load('optionPrices');

        $variant = $this->variant('TST-0005-CEV-2li');
        $variant->setRelation('product', $product);

        $this->assertSame(0.0, $variant->effectivePrice());
    }

    public function test_a_manual_price_overrides_the_rules(): void
    {
        $product = $this->optionProduct();

        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '3lu',
            'price_delta' => 900,
        ]);

        $product->load('optionPrices');

        $variant = $this->variant('TST-0005-CEV-3lu');
        $variant->update(['price' => 7777]);
        $variant->setRelation('product', $product);

        $this->assertTrue($variant->hasManualPrice());
        $this->assertSame(7777.0, $variant->fresh()->effectivePrice());
    }

    public function test_the_cart_charges_the_calculated_price(): void
    {
        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '3lu',
            'price_delta' => 900,
        ]);

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'secenek' => ['renk' => 'ceviz', 'olcu' => '3lu'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'unit_price' => (float) $product->price + 900,
        ]);
    }

    public function test_pricing_a_whole_product_page_costs_one_extra_query(): void
    {
        $product = $this->optionProduct();
        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '3lu',
            'price_delta' => 900,
        ]);

        $fresh = Product::with(['variants', 'optionPrices'])->findOrFail($product->id);

        DB::enableQueryLog();

        foreach ($fresh->variants as $variant) {
            $variant->effectivePrice();
        }

        $queries = count(DB::getRawQueryLog());
        DB::disableQueryLog();

        // Iliskiler onceden yuklendiginde fiyatlama HIC sorgu acmamali
        $this->assertSame(0, $queries, 'Varyant fiyatlamasi ek sorgu aciyor (N+1).');
    }

    public function test_a_rule_entered_in_the_admin_reaches_the_storefront(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $product = $this->optionProduct();
        $product->variants()->update(['price' => null]);

        Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])->fillForm([
            'optionPrices' => [
                ['attribute_slug' => 'olcu', 'value_slug' => '3lu', 'price_delta' => 1500],
            ],
        ])->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_option_prices', [
            'product_id' => $product->id,
            'value_slug' => '3lu',
            'price_delta' => 1500,
        ]);

        // Tek kural girildi; magazada UC varyantin fiyati birden degisti
        $beklenen = money((float) $product->price + 1500);

        $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertSuccessful()
            ->assertSee($beklenen, false);
    }

    public function test_the_rules_disappear_with_the_product(): void
    {
        $product = $this->optionProduct();
        $product->optionPrices()->create([
            'attribute_slug' => 'olcu',
            'value_slug' => '3lu',
            'price_delta' => 900,
        ]);

        $id = $product->id;
        $product->delete();

        $this->assertDatabaseMissing('product_option_prices', ['product_id' => $id]);
    }
}
