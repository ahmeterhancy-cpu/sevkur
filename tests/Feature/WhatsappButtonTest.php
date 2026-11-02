<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Tests\ShopTestCase;

/**
 * WhatsApp dugmesi.
 *
 * Katalogdaki urunlerin hicbirinde fiyat yok; musteri fiyati sormak icin
 * bir kanal ariyor. Dugme her sayfada duruyor ve urun sayfasindan urun
 * adiyla aciliyor — musteri hangi urun icin yazdigini elle yazmasin.
 */
class WhatsappButtonTest extends ShopTestCase
{
    public function test_a_local_number_becomes_an_international_wa_me_link(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $this->assertSame('https://wa.me/905365678548', whatsapp_url());
    }

    public function test_a_message_is_appended_and_encoded(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $url = whatsapp_url('Merhaba, Köşe Takımı hakkında bilgi almak istiyorum.');

        $this->assertStringStartsWith('https://wa.me/905365678548?text=', $url);
        $this->assertStringContainsString('K%C3%B6%C5%9Fe', $url, 'Türkçe harfler kodlanmalı.');
        $this->assertStringNotContainsString(' ', $url);
    }

    public function test_an_empty_message_leaves_the_link_bare(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $this->assertSame('https://wa.me/905365678548', whatsapp_url('   '));
        $this->assertSame('https://wa.me/905365678548', whatsapp_url(null));
    }

    public function test_without_a_number_there_is_no_link(): void
    {
        Setting::putMany(['whatsapp_number' => '']);

        $this->assertNull(whatsapp_url());
    }

    public function test_the_button_is_on_the_home_page(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertSee('wa-fab', false)
            ->assertSee('https://wa.me/905365678548', false);
    }

    public function test_the_product_page_prefills_the_product_name(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $product = Product::active()->firstOrFail();

        $html = $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertOk()
            ->getContent();

        // Mesajda urunun adi gecmeli; bosluklar kodlanmis halde aranir.
        $needle = rawurlencode($product->t('name'));

        $this->assertStringContainsString($needle, $html, 'Ürün adı WhatsApp mesajına girmemiş.');
    }

    public function test_without_a_number_the_button_disappears(): void
    {
        Setting::putMany(['whatsapp_number' => '']);

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertDontSee('wa-fab', false);
    }

    public function test_the_ask_price_button_opens_whatsapp_with_the_product(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        $product = Product::active()->firstOrFail();
        $product->update(['price' => 0]);
        $product->variants()->delete();

        $html = $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.e(ask_price_url($product->fresh()->t('name'))).'"',
            $html
        );
    }

    public function test_without_a_number_the_ask_price_button_goes_to_the_contact_page(): void
    {
        Setting::putMany(['whatsapp_number' => '']);

        $product = Product::active()->firstOrFail();
        $product->update(['price' => 0]);
        $product->variants()->delete();

        $this->assertSame(localized_route('contact'), ask_price_url($product->t('name')));
        $this->assertFalse(ask_price_is_whatsapp());

        // Olu dugme kalmasin: baglanti yine de calisir olmali.
        $this->get(localized_route('product', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee(e(localized_route('contact')), false);
    }

    public function test_each_card_carries_its_own_product_name(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48']);

        Product::query()->update(['price' => 0]);
        ProductVariant::query()->delete();

        $html = $this->get(localized_route('shop'))->assertOk()->getContent();

        $isimler = Product::active()->take(2)->get()
            ->map(fn (Product $p) => rawurlencode($p->t('name')));

        foreach ($isimler as $isim) {
            $this->assertStringContainsString($isim, $html);
        }
    }
}
