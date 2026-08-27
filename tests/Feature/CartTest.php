<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\CartService;
use Tests\ShopTestCase;

class CartTest extends ShopTestCase
{
    public function test_product_can_be_added_to_cart(): void
    {
        $product = $this->product(1);

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_quantity_is_capped_by_stock(): void
    {
        $product = $this->product(1);
        $product->update(['stock' => 3]);

        $response = $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 9,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 3]);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $product = $this->product(1);
        $product->update(['stock' => 0]);

        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_variant_price_is_used_when_present(): void
    {
        $product = $this->product(5);
        $variant = $product->variants()->first();
        $variant->update(['price' => 9999]);

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ])->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'unit_price' => 9999,
        ]);
    }

    public function test_variant_belonging_to_another_product_is_ignored(): void
    {
        $product = $this->product(1);
        $foreignVariant = $this->product(5)->variants()->first();

        $this->postJson(localized_route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $foreignVariant->id,
        ])->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);
    }

    public function test_cart_item_can_be_updated_and_removed(): void
    {
        $product = $this->product(2);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id]);

        $item = CartItem::first();

        $this->postJson(localized_route('cart.update'), ['item_id' => $item->id, 'quantity' => 4])
            ->assertOk()->assertJson(['count' => 4]);

        $this->postJson(localized_route('cart.remove'), ['item_id' => $item->id])
            ->assertOk()->assertJson(['count' => 0, 'empty' => true]);
    }

    public function test_cart_item_of_another_session_cannot_be_touched(): void
    {
        $product = $this->product(2);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id]);
        $item = CartItem::first();

        // Yeni oturum = yeni sepet
        $this->flushSession();

        $this->postJson(localized_route('cart.remove'), ['item_id' => $item->id])->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_percentage_coupon_is_applied(): void
    {
        $product = $this->product(3); // 3000 TL
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id]);

        $this->post(localized_route('cart.coupon'), ['code' => 'HOSGELDIN10'])
            ->assertRedirect();

        $totals = app(CartService::class)->totals();

        $this->assertSame(3000.0, $totals['subtotal']);
        $this->assertSame(300.0, $totals['discount']);
        $this->assertSame(2700.0, $totals['total']);
    }

    public function test_coupon_below_minimum_total_is_rejected(): void
    {
        $product = $this->product(1); // 1000 TL, minimum 3000
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id]);

        $this->post(localized_route('cart.coupon'), ['code' => 'HOSGELDIN10'])
            ->assertSessionHas('error');

        $this->assertNull(app(CartService::class)->totals()['coupon']);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        Coupon::where('code', 'HOSGELDIN10')->update(['ends_at' => now()->subDay()]);

        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(5)->id]);

        $this->post(localized_route('cart.coupon'), ['code' => 'HOSGELDIN10'])
            ->assertSessionHas('error');
    }

    public function test_discount_never_exceeds_subtotal(): void
    {
        Coupon::create([
            'code' => 'BUYUK',
            'type' => 'fixed',
            'value' => 99999,
            'is_active' => true,
        ]);

        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);
        $this->post(localized_route('cart.coupon'), ['code' => 'BUYUK']);

        $totals = app(CartService::class)->totals();

        $this->assertSame(1000.0, $totals['discount']);
        $this->assertSame(0.0, $totals['total']);
    }
}
