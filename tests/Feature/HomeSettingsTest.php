<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Http\Controllers\HomeController;
use App\Models\Setting;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Ana sayfa olculeri yonetimden.
 *
 * Ray sayisi, ray boyutu, kategori kutusu sayisi ve slider suresi kodda
 * sabit yazilidi; magaza sahibi degistiremiyordu. Artik ayardan okunuyor,
 * ayar girilmemisse koddaki varsayilan kullaniliyor.
 */
class HomeSettingsTest extends ShopTestCase
{
    private function rails()
    {
        $method = new \ReflectionMethod(HomeController::class, 'categoryRails');

        return $method->invoke(new HomeController);
    }

    private function tiles()
    {
        $method = new \ReflectionMethod(HomeController::class, 'featuredCategories');

        return $method->invoke(new HomeController);
    }

    public function test_the_rail_count_follows_the_setting(): void
    {
        Setting::putMany(['home_rails_min' => 1, 'home_rails_count' => 1]);
        $this->assertLessThanOrEqual(1, $this->rails()->count());

        Setting::putMany(['home_rails_count' => 2]);
        $this->assertLessThanOrEqual(2, $this->rails()->count());
    }

    public function test_the_rail_size_follows_the_setting(): void
    {
        Setting::putMany(['home_rails_min' => 1, 'home_rails_count' => 1, 'home_rails_size' => 5]);

        $rail = $this->rails()->first();

        $this->assertNotNull($rail, 'Ray üretilmedi.');
        $this->assertLessThanOrEqual(5, $rail['urunler']->count());
    }

    public function test_rails_can_be_switched_off(): void
    {
        Setting::putMany(['home_rails_min' => 1, 'home_rails_count' => 3]);

        $acik = substr_count(
            $this->get(localized_route('home'))->assertOk()->getContent(),
            'data-rail-track'
        );

        Setting::putMany(['home_rails_enabled' => false]);

        $kapali = substr_count(
            $this->get(localized_route('home'))->assertOk()->getContent(),
            'data-rail-track'
        );

        // "Yeni Gelenler" de ayni ray isaretini kullaniyor; kapatinca yalnizca
        // ODA raylari dusmeli, sayfa ayakta kalmali.
        // categoryRails() ac/kapa ayarini bilmiyor; o kontrol denetleyicide,
        // bu yuzden olcu ciktidaki ray sayisi.
        $this->assertGreaterThan($kapali, $acik, 'Raylar kapatılınca sayı düşmedi.');
        $this->assertSame(1, $kapali, 'Yalnızca Yeni Gelenler rayı kalmalı.');
    }

    public function test_the_tile_count_follows_the_setting(): void
    {
        Setting::putMany(['home_categories_count' => 3]);
        $this->assertLessThanOrEqual(3, $this->tiles()->count());
    }

    public function test_the_tile_count_stays_within_safe_bounds(): void
    {
        // Mozaik 4x2 izgaraya oturuyor; disari tasan deger kirpilir.
        Setting::putMany(['home_categories_count' => 99]);
        $this->assertLessThanOrEqual(5, $this->tiles()->count());

        Setting::putMany(['home_categories_count' => 0]);
        $this->assertLessThanOrEqual(3, $this->tiles()->count());
    }

    public function test_the_slider_interval_follows_the_setting(): void
    {
        Setting::putMany(['home_slider_interval' => 9000]);

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertSee('data-slider="9000"', false);
    }

    public function test_a_too_fast_interval_is_raised_to_a_usable_floor(): void
    {
        Setting::putMany(['home_slider_interval' => 10]);

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertSee('data-slider="2000"', false);
    }

    public function test_the_new_fields_save_from_the_admin_page(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'home_rails_count' => 2,
                'home_rails_size' => 8,
                'home_slider_interval' => 7000,
                'ask_price_whatsapp' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2', (string) Setting::get('home_rails_count'));
        $this->assertSame('8', (string) Setting::get('home_rails_size'));
        $this->assertSame('7000', (string) Setting::get('home_slider_interval'));
        $this->assertFalse((bool) Setting::get('ask_price_whatsapp'));
    }

    public function test_turning_off_whatsapp_sends_the_button_to_the_contact_page(): void
    {
        Setting::putMany(['whatsapp_number' => '0536 567 85 48', 'ask_price_whatsapp' => false]);

        $this->assertSame(localized_route('contact'), ask_price_url('Odessa Koltuk Takımı'));
        $this->assertFalse(ask_price_is_whatsapp());
    }

    public function test_a_custom_whatsapp_message_replaces_the_default(): void
    {
        Setting::putMany([
            'whatsapp_number' => '0536 567 85 48',
            'whatsapp_product_message' => 'Selam, :product için fiyat öğrenebilir miyim?',
        ]);

        $url = ask_price_url('Odessa Koltuk Takımı');

        $this->assertStringContainsString(rawurlencode('Selam, Odessa Koltuk Takımı için'), $url);
    }
}
