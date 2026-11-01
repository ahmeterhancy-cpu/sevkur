<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\ShopTestCase;

/**
 * Fiyati girilmemis urunler.
 *
 * Katalogdan aktarilan urunler fiyatsiz gelir ama YAYINDADIR: musteri urunu
 * gorsun, fotografina baksin, fiyat icin sorsun. Satilamazlar — yoksa sifir
 * liraya siparis verilebilirdi. Fiyat girilir girilmez satisa acilirlar.
 */
class PriceMissingTest extends ShopTestCase
{
    private function freeProduct(): Product
    {
        $product = $this->product(1);
        $product->update(['price' => 0, 'compare_at_price' => null]);
        $product->variants()->delete();

        return $product->fresh();
    }

    public function test_a_product_without_a_price_cannot_be_added_to_the_cart(): void
    {
        $product = $this->freeProduct();

        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id])
            ->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_entering_a_price_makes_it_sellable_again(): void
    {
        $product = $this->freeProduct();
        $product->update(['price' => 4200]);

        $this->postJson(localized_route('cart.add'), ['product_id' => $product->fresh()->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'unit_price' => 4200,
        ]);
    }

    public function test_a_variant_price_counts_even_when_the_product_price_is_empty(): void
    {
        // Urun fiyati bos olsa da varyant fiyatliysa satilabilir olmali
        $product = $this->product(5);
        $product->update(['price' => 0]);

        $this->assertTrue($product->fresh()->hasPrice());
    }

    public function test_the_storefront_shows_a_notice_instead_of_zero_lira(): void
    {
        $product = $this->freeProduct();

        $html = $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertSuccessful()
            ->assertSee(__('shop.price_on_request'))
            ->getContent();

        // Fiyat alani hic basilmamali. ("0,00 ₺" aramak yaniltir: kargo
        // esigindeki "15.000,00 ₺" metni de o diziyi icerir.)
        $this->assertStringNotContainsString('data-product-price', $html);
    }

    public function test_a_priced_product_still_shows_its_price(): void
    {
        $product = $this->product(2);

        $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertSuccessful()
            ->assertSee(money($product->price), false)
            ->assertDontSee(__('shop.price_on_request'));
    }

    public function test_the_imported_catalogue_is_published_but_unpriced(): void
    {
        // Oben urunleri gercek veritabaninda; testte yoksa atla
        $imported = Product::whereHas('brand', fn ($b) => $b->where('slug', 'oben'))->get();

        if ($imported->isEmpty()) {
            $this->markTestSkipped('Oben katalogu bu veritabaninda yok.');
        }

        foreach ($imported as $product) {
            $this->assertTrue($product->is_active, $product->slug.' yayinda degil.');
        }
    }
    public function test_the_product_page_offers_contact_instead_of_add_to_cart(): void
    {
        $product = $this->freeProduct();

        $html = $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertOk()
            ->getContent();

        // Calisir gorunen bir sepet dugmesi musteriyi hataya goturuyordu.
        // Benzer urun kartlarinda da sepet formu var, bu yuzden kontrol BU
        // urunun kimligine baglaniyor.
        $this->assertStringNotContainsString(
            'name="product_id" value="'.$product->id.'"',
            $html
        );
        $this->assertStringContainsString(__('shop.ask_price'), $html);
    }

    public function test_a_priced_product_still_has_the_cart_form(): void
    {
        $product = $this->product(1);
        $product->update(['price' => 4200]);

        $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('name="product_id" value="'.$product->id.'"', false);
    }
}
