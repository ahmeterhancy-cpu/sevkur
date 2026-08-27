<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Support\Locales;
use Illuminate\Support\Facades\Mail;
use Tests\ShopTestCase;

/**
 * Siparis onay e-postasi.
 *
 * Mailable "view:" ile tanimlanmisti ama sablon markdown mail bilesenleri
 * kullaniyordu; gonderim sirasinda patliyor, hata try/catch icinde yutulup
 * yalnizca kutuge dusuyordu. Musteri hicbir zaman e-posta almiyordu.
 */
class OrderMailTest extends ShopTestCase
{
    private function makeOrder(string $locale = 'tr'): Order
    {
        app()->setLocale($locale);

        $this->postJson(localized_route('cart.add', [], $locale), [
            'product_id' => $this->product(2)->id,
            'quantity' => 2,
        ]);

        $this->post(localized_route('checkout.store', [], $locale), [
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

        return Order::latest('id')->firstOrFail();
    }

    public function test_confirmation_mail_renders_in_every_locale(): void
    {
        foreach (Locales::all() as $locale) {
            $order = $this->makeOrder($locale);
            $order->forceFill(['locale' => $locale])->save();

            $rendered = (new OrderPlaced($order))->render();

            $this->assertStringContainsString($order->number, $rendered, "$locale postasinda siparis no yok");
            $this->assertStringNotContainsString('shop.mail_order', $rendered, "$locale postasinda cevrilmemis anahtar var");
            $this->assertStringContainsString('₺', $rendered, "$locale postasinda tutar yok");
        }
    }

    public function test_mail_is_actually_sent_when_payment_succeeds(): void
    {
        Mail::fake();

        $order = $this->makeOrder();

        $this->post(localized_route('checkout.simulate', ['order' => $order->number]), ['result' => 'success'])
            ->assertRedirect();

        Mail::assertSent(OrderPlaced::class, fn (OrderPlaced $mail) => $mail->order->is($order));
    }

    public function test_no_mail_is_sent_when_payment_fails(): void
    {
        Mail::fake();

        $order = $this->makeOrder();

        $this->post(localized_route('checkout.simulate', ['order' => $order->number]), ['result' => 'fail']);

        Mail::assertNothingSent();
    }
}
