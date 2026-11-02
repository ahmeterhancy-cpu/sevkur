<?php

namespace Tests\Feature;

use App\Models\Product;
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
}
