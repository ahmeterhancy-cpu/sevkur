<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use Tests\ShopTestCase;

class CheckoutTest extends ShopTestCase
{
    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Deniz',
            'last_name' => 'Yılmaz',
            'email' => 'deniz@example.com',
            'phone' => '+90 555 111 22 33',
            'city' => 'Edirne',
            'district' => 'Merkez',
            'address' => 'Sanayi Mah. No: 1',
            'postcode' => '22000',
            'shipping_method_id' => ShippingMethod::where('code', 'standart')->first()->id,
            'billing_same' => 1,
            'terms' => 1,
        ], $overrides);
    }

    public function test_checkout_requires_a_cart(): void
    {
        $this->get(localized_route('checkout'))->assertRedirect(localized_route('cart'));
    }

    public function test_order_is_created_and_stock_is_reduced(): void
    {
        $product = $this->product(2);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);

        $this->post(localized_route('checkout.store'), $this->payload())->assertRedirect();

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame(6000.0, (float) $order->subtotal);
        $this->assertSame(499.0, (float) $order->shipping_total);
        $this->assertSame(6499.0, (float) $order->grand_total);
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_free_shipping_threshold_applies(): void
    {
        // Urun 5 seceneklidir; secenekli urun varyant secilmeden sepete
        // eklenemez. Kargo esigi icin varyantsiz urun 4 kullanilir.
        $product = $this->product(4); // 4000 TL
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id, 'quantity' => 4]);

        $this->post(localized_route('checkout.store'), $this->payload())->assertRedirect();

        $order = Order::first();

        $this->assertSame(16000.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->shipping_total);
    }

    public function test_order_is_blocked_when_stock_disappears_before_submit(): void
    {
        $product = $this->product(1);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id, 'quantity' => 5]);

        // Baska bir musteri stogu tuketti
        $product->update(['stock' => 2]);

        $this->post(localized_route('checkout.store'), $this->payload())
            ->assertRedirect(localized_route('cart'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);

        $this->post(localized_route('checkout.store'), $this->payload(['terms' => null]))
            ->assertSessionHasErrors('terms');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_payment_simulation_marks_order_paid_and_increments_sold_count(): void
    {
        $product = $this->product(2);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(localized_route('checkout.store'), $this->payload());

        $order = Order::first();
        $soldBefore = $product->fresh()->sold_count;

        $this->post(localized_route('checkout.simulate', ['order' => $order->number]), ['result' => 'success'])
            ->assertRedirect(localized_route('checkout.thanks', ['order' => $order->number]));

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame($soldBefore + 2, $product->fresh()->sold_count);
    }

    public function test_failed_payment_restores_stock(): void
    {
        $product = $this->product(2);
        $this->postJson(localized_route('cart.add'), ['product_id' => $product->id, 'quantity' => 4]);
        $this->post(localized_route('checkout.store'), $this->payload());

        $order = Order::first();
        $this->assertSame(6, $product->fresh()->stock);

        $this->post(localized_route('checkout.simulate', ['order' => $order->number]), ['result' => 'fail'])
            ->assertRedirect(localized_route('checkout.failed', ['order' => $order->number]));

        $order->refresh();

        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_guest_cannot_view_someone_elses_order(): void
    {
        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);
        $this->post(localized_route('checkout.store'), $this->payload());

        $order = Order::first();

        // Farkli bir ziyaretci
        $this->flushSession();

        $this->get(localized_route('checkout.thanks', ['order' => $order->number]))->assertForbidden();
    }

    public function test_order_is_linked_to_the_logged_in_user(): void
    {
        $user = User::where('email', 'musteri@example.com')->firstOrFail();

        $this->actingAs($user);
        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);
        $this->post(localized_route('checkout.store'), $this->payload(['save_address' => 1]));

        $order = Order::first();

        $this->assertSame($user->id, $order->user_id);
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'city' => 'Edirne']);
    }

    public function test_customer_can_track_order_with_email(): void
    {
        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);
        $this->post(localized_route('checkout.store'), $this->payload());

        $order = Order::first();

        $this->post(localized_route('track'), [
            'number' => $order->number,
            'email' => 'DENIZ@example.com',
        ])->assertOk()->assertSee($order->number);

        $this->post(localized_route('track'), [
            'number' => $order->number,
            'email' => 'baska@example.com',
        ])->assertOk()->assertSee(__('shop.track_not_found'));
    }
}
