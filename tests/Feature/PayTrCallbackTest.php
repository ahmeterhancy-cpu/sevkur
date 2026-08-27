<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingMethod;
use Tests\ShopTestCase;

class PayTrCallbackTest extends ShopTestCase
{
    private const KEY = 'test-key';

    private const SALT = 'test-salt';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'paytr.merchant_id' => '123456',
            'paytr.merchant_key' => self::KEY,
            'paytr.merchant_salt' => self::SALT,
            'paytr.allow_simulation' => false,
        ]);
    }

    private function makeOrder(int $quantity = 2): Order
    {
        $this->postJson(localized_route('cart.add'), [
            'product_id' => $this->product(2)->id,
            'quantity' => $quantity,
        ]);

        $this->post(localized_route('checkout.store'), [
            'first_name' => 'Deniz',
            'last_name' => 'Yılmaz',
            'email' => 'deniz@example.com',
            'phone' => '+90 555 111 22 33',
            'city' => 'Edirne',
            'address' => 'Sanayi Mah. No: 1',
            'shipping_method_id' => ShippingMethod::where('code', 'standart')->first()->id,
            'billing_same' => 1,
            'terms' => 1,
        ]);

        $order = Order::firstOrFail();
        $order->update(['merchant_oid' => 'TESTOID'.$order->id]);

        return $order->fresh();
    }

    /** @return array<string, string> */
    private function callbackPayload(Order $order, string $status = 'success', ?int $amount = null): array
    {
        $total = (string) ($amount ?? (int) round((float) $order->grand_total * 100));

        $hash = base64_encode(hash_hmac(
            'sha256',
            $order->merchant_oid.self::SALT.$status.$total,
            self::KEY,
            true
        ));

        return [
            'merchant_oid' => $order->merchant_oid,
            'status' => $status,
            'total_amount' => $total,
            'hash' => $hash,
        ];
    }

    public function test_valid_success_callback_marks_order_paid(): void
    {
        $order = $this->makeOrder();

        $this->post('/paytr/callback', $this->callbackPayload($order))
            ->assertOk()
            ->assertSee('OK');

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_callback_with_bad_hash_is_rejected(): void
    {
        $order = $this->makeOrder();

        $payload = $this->callbackPayload($order);
        $payload['hash'] = 'bozuk-imza';

        $this->post('/paytr/callback', $payload)->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_failed_callback_cancels_order_and_restores_stock(): void
    {
        $product = $this->product(2);
        $order = $this->makeOrder(3);

        $this->assertSame(7, $product->fresh()->stock);

        $this->post('/paytr/callback', $this->callbackPayload($order, 'failed'))->assertOk();

        $order->refresh();

        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_repeated_callback_does_not_double_count_sales(): void
    {
        $product = $this->product(2);
        $order = $this->makeOrder(2);
        $soldBefore = $product->fresh()->sold_count;

        $payload = $this->callbackPayload($order);

        $this->post('/paytr/callback', $payload)->assertOk();
        $this->post('/paytr/callback', $payload)->assertOk();

        $this->assertSame($soldBefore + 2, $product->fresh()->sold_count);
    }

    public function test_amount_mismatch_is_logged_but_order_still_marked_paid(): void
    {
        $order = $this->makeOrder();

        $this->post('/paytr/callback', $this->callbackPayload($order, 'success', 1))->assertOk();

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => 'amount_mismatch',
        ]);
    }

    public function test_unknown_order_is_acknowledged_without_error(): void
    {
        $hash = base64_encode(hash_hmac('sha256', 'YOKOID'.self::SALT.'success100', self::KEY, true));

        $this->post('/paytr/callback', [
            'merchant_oid' => 'YOKOID',
            'status' => 'success',
            'total_amount' => '100',
            'hash' => $hash,
        ])->assertOk()->assertSee('OK');
    }
}
