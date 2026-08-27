<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\CurrencyService;
use Tests\ShopTestCase;

class CurrencyTest extends ShopTestCase
{
    public function test_base_currency_is_try(): void
    {
        $this->assertSame('TRY', app(CurrencyService::class)->base()->code);
    }

    public function test_visitor_can_switch_currency(): void
    {
        $this->post(localized_route('currency.switch'), ['code' => 'EUR'])->assertRedirect();

        $this->assertSame('EUR', session(CurrencyService::SESSION_KEY));
    }

    public function test_unknown_currency_is_ignored(): void
    {
        $this->post(localized_route('currency.switch'), ['code' => 'XXX'])->assertRedirect();

        $this->assertNull(session(CurrencyService::SESSION_KEY));
    }

    public function test_manual_rate_overrides_automatic_rate(): void
    {
        $eur = Currency::where('code', 'EUR')->firstOrFail();
        $eur->update(['rate' => 0.02, 'manual_rate' => 0.03]);

        app(CurrencyService::class)->forgetCache();

        $this->assertSame(0.03, round($eur->fresh()->effectiveRate(), 4));
    }

    public function test_margin_is_applied_on_top_of_the_rate(): void
    {
        $eur = Currency::where('code', 'EUR')->firstOrFail();
        $eur->update(['rate' => 0.02, 'manual_rate' => null, 'margin_percent' => 10]);

        $this->assertSame(0.022, round($eur->fresh()->effectiveRate(), 4));
    }

    public function test_amount_is_converted_and_formatted(): void
    {
        $eur = Currency::where('code', 'EUR')->firstOrFail();
        $eur->update(['rate' => 0.02, 'manual_rate' => null, 'margin_percent' => 0]);

        app(CurrencyService::class)->forgetCache();
        session([CurrencyService::SESSION_KEY => 'EUR']);

        $this->assertSame('20,00 €', money(1000));
    }

    public function test_base_currency_helper_always_formats_in_try(): void
    {
        session([CurrencyService::SESSION_KEY => 'EUR']);

        $this->assertSame('1.234,50 ₺', money_base(1234.5));
    }

    public function test_locale_falls_back_to_its_default_currency(): void
    {
        $service = app(CurrencyService::class);

        app()->setLocale('bg');
        $this->assertSame('BGN', $service->current()->code);

        app()->setLocale('el');
        $this->assertSame('EUR', $service->current()->code);

        app()->setLocale('tr');
        $this->assertSame('TRY', $service->current()->code);
    }

    public function test_orders_are_always_stored_in_try_regardless_of_display_currency(): void
    {
        session([CurrencyService::SESSION_KEY => 'EUR']);

        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);

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

        $this->assertSame(1000.0, (float) $order->subtotal);
        $this->assertSame('EUR', $order->display_currency);
    }
}
