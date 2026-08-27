<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Secenekli varyantlar: Renk ve Olcu gibi eksenler.
 *
 * Bir varyant tek basina bir "isim" degil, seceneklerin KOMBINASYONUDUR.
 * Musteri her eksenden birer deger secer, sunucu bunu tek bir varyanta
 * cevirir. Tarayicidan gelen varyant id'sine guvenilmez.
 */
class VariantOptionTest extends ShopTestCase
{
    private function optionProduct(): Product
    {
        return $this->product(5);
    }

    // -------------------------------------------------------------- eksenler

    public function test_axes_are_derived_from_the_variants(): void
    {
        $axes = $this->optionProduct()->variantAxes();

        $this->assertCount(2, $axes);

        $this->assertSame('renk', $axes[0]['attribute']->slug);
        $this->assertSame(['ceviz', 'antrasit'], array_map(fn ($v) => $v->slug, $axes[0]['values']));

        $this->assertSame('olcu', $axes[1]['attribute']->slug);
        $this->assertSame(['2li', '3lu'], array_map(fn ($v) => $v->slug, $axes[1]['values']));
    }

    public function test_only_values_actually_used_by_the_product_are_offered(): void
    {
        $colours = $this->optionProduct()->variantAxes()[0]['values'];

        // Nitelikte 8 renk var; urun yalnizca ikisini kullaniyor
        $this->assertCount(2, $colours);
        $this->assertNotContains('krem', array_map(fn ($v) => $v->slug, $colours));
    }

    public function test_a_product_without_options_has_no_axes(): void
    {
        $this->assertSame([], $this->product(1)->variantAxes());
    }

    // ---------------------------------------------------------------- etiket

    public function test_the_label_is_built_from_the_options_in_every_language(): void
    {
        $variant = $this->optionProduct()->variants->firstWhere('sku', 'TST-0005-CEV-3lu');

        $this->assertSame('Ceviz / 3’lü', $variant->label('tr'));
        $this->assertSame('Walnut / 3-Seater', $variant->label('en'));
        $this->assertSame('Орех / 3-местен', $variant->label('bg'));
        $this->assertSame('Καρυδί / 3 θέσεων', $variant->label('el'));
    }

    public function test_a_manual_name_wins_over_the_generated_one(): void
    {
        $variant = $this->optionProduct()->variants->first();
        $variant->update(['name' => ['tr' => 'Özel Sipariş']]);

        $this->assertSame('Özel Sipariş', $variant->fresh()->label('tr'));
    }

    // -------------------------------------------------------------- eslesme

    public function test_a_partial_selection_never_matches(): void
    {
        $variant = $this->optionProduct()->variants->firstWhere('sku', 'TST-0005-CEV-2li');

        // Yalnizca renk secilirse hangi olcu oldugu belirsizdir
        $this->assertFalse($variant->matches(['renk' => 'ceviz']));
        $this->assertTrue($variant->matches(['renk' => 'ceviz', 'olcu' => '2li']));
    }

    public function test_empty_options_are_not_stored(): void
    {
        $variant = $this->optionProduct()->variants->first();
        $variant->update(['options' => ['renk' => 'ceviz', 'olcu' => null, 'stil' => '']]);

        $this->assertSame(['renk' => 'ceviz'], $variant->fresh()->options);
    }

    // ------------------------------------------------------------ urun sayfasi

    public function test_the_product_page_shows_a_picker_for_each_axis(): void
    {
        $response = $this->get(localized_route('product', ['product' => $this->optionProduct()->slug]));

        $response->assertSuccessful()
            ->assertSee('secenek[renk]', false)
            ->assertSee('secenek[olcu]', false)
            ->assertSee('variant-swatch', false)   // renk kutucugu
            ->assertSee('3’lü', false);
    }

    public function test_the_first_in_stock_combination_is_preselected(): void
    {
        $html = $this->get(localized_route('product', ['product' => $this->optionProduct()->slug]))
            ->assertSuccessful()
            ->getContent();

        // Stokta olan ilk kombinasyon ceviz/2li
        $this->assertMatchesRegularExpression('/id="secenek-renk-ceviz"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/id="secenek-olcu-2li"[^>]*checked/', $html);

        // Ayni eksende ikinci bir deger isaretli kalmamali
        $this->assertDoesNotMatchRegularExpression('/id="secenek-renk-antrasit"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="secenek-olcu-3lu"[^>]*checked/', $html);
    }

    // ---------------------------------------------------------------- sepet

    public function test_adding_to_cart_resolves_the_variant_from_the_options(): void
    {
        $product = $this->optionProduct();

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'secenek' => ['renk' => 'antrasit', 'olcu' => '2li'],
        ])->assertSuccessful();

        $expected = $product->variants->firstWhere('sku', 'TST-0005-ANT-2li');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $expected->id,
        ]);
    }

    public function test_the_variant_price_is_used_not_the_product_price(): void
    {
        $product = $this->optionProduct();
        $variant = $product->variants->firstWhere('sku', 'TST-0005-CEV-3lu');

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'secenek' => ['renk' => 'ceviz', 'olcu' => '3lu'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'unit_price' => $variant->effectivePrice(),
        ]);

        $this->assertNotSame((float) $product->price, $variant->effectivePrice());
    }

    public function test_a_combination_that_does_not_exist_is_refused(): void
    {
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $this->optionProduct()->id,
            'secenek' => ['renk' => 'krem', 'olcu' => '3lu'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_an_out_of_stock_combination_is_refused(): void
    {
        // ShopTestCase'te antrasit/3lu stoksuz birakildi
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $this->optionProduct()->id,
            'secenek' => ['renk' => 'antrasit', 'olcu' => '3lu'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_product_with_options_cannot_be_added_without_choosing(): void
    {
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $this->optionProduct()->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_variant_id_belonging_to_another_product_is_ignored(): void
    {
        $other = $this->product(1);
        $variant = $this->optionProduct()->variants->first();

        // Baska urunun varyant id'si yok sayilir; urun 1'in secenegi olmadigi
        // icin ekleme varyantsiz olarak tamamlanir
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $other->id,
            'variant_id' => $variant->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $other->id,
            'product_variant_id' => null,
        ]);
    }

    // -------------------------------------------------------------- siparis

    public function test_the_order_item_keeps_the_options_it_was_bought_with(): void
    {
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $this->optionProduct()->id,
            'secenek' => ['renk' => 'ceviz', 'olcu' => '3lu'],
        ])->assertSuccessful();

        $this->post(localized_route('checkout.store'), $this->payload())->assertRedirect();

        $item = Order::first()->items->first();

        $this->assertSame('Ceviz / 3’lü', $item->options['variant']);
        $this->assertSame(['renk' => 'ceviz', 'olcu' => '3lu'], $item->options['secenekler']);
    }

    // ----------------------------------------------------------------- panel

    public function test_the_admin_form_offers_a_picker_for_each_variant_axis(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $product = $this->optionProduct();

        $state = Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])->get('data.variants');

        $first = reset($state);

        // Secenekler forma varyantin kendi alanlari olarak gelmeli
        $this->assertSame('ceviz', $first['options']['renk'] ?? null);
        $this->assertSame('2li', $first['options']['olcu'] ?? null);
    }

    public function test_saving_from_the_admin_keeps_the_variant_options(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $product = $this->optionProduct();
        $before = $product->variants->pluck('options', 'sku');

        Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])->call('save')->assertHasNoFormErrors();

        $after = $product->fresh()->variants->pluck('options', 'sku');

        $this->assertEquals($before->all(), $after->all(), 'Kaydetmek varyant seceneklerini bozdu.');
    }

    public function test_the_generator_opens_every_missing_combination(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $product = $this->optionProduct();
        $before = $product->variants->count();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('kombinasyon_uret', [
                'eksen' => ['renk' => ['ceviz', 'antrasit', 'krem'], 'olcu' => ['2li', '3lu']],
                'stock' => 5,
            ]);

        $after = $product->fresh()->variants;
        $fresh = $after->firstWhere('options', ['renk' => 'krem', 'olcu' => '2li']);

        // 3x2 = 6 kombinasyon istendi, dordu zaten vardi -> yalnizca 2 yeni
        $this->assertSame($before + 2, $after->count());
        $this->assertNotNull($fresh);
        $this->assertSame(5, $fresh->stock);
    }

    public function test_the_generator_does_not_duplicate_existing_combinations(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $product = $this->optionProduct();
        $before = $product->variants->count();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('kombinasyon_uret', [
                'eksen' => ['renk' => ['ceviz', 'antrasit'], 'olcu' => ['2li', '3lu']],
                'stock' => 0,
            ]);

        $this->assertSame($before, $product->fresh()->variants->count());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'email' => 'musteri@example.com',
            'phone' => '05001112233',
            'first_name' => 'Test',
            'last_name' => 'Müşteri',
            'address' => 'Adres 1',
            'city' => 'Edirne',
            'district' => 'Merkez',
            'postal_code' => '22000',
            'shipping_method_id' => ShippingMethod::active()->first()->id,
            'payment_method' => 'paytr',
            'terms' => '1',
        ];
    }
}
