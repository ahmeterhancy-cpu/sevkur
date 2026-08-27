<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\User;
use App\Support\Locales;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Ana sayfanin panelden yonetilen kisimlari.
 *
 * Bolum basliklari ve alt bilgi vaatleri ayar olarak saklanir. Bunlarin
 * DILE GORE okunmasi sart: dort dilli sitede tek dilli ayar, Ingilizce
 * sayfada Turkce baslik demektir.
 */
class HomeContentTest extends ShopTestCase
{
    /** Musteriye gorunen, dile gore saklanmasi gereken ayarlar. */
    private const LOCALISED = [
        'top_bar_text', 'working_hours', 'footer_about',
        'promise_shipping', 'promise_return_title', 'promise_return',
        'promise_secure_title', 'promise_secure',
        'promise_support_title', 'promise_support',
        'home_categories_title', 'home_bestsellers_title', 'home_bestsellers_sub',
        'home_deals_title', 'home_new_title', 'home_blog_title',
    ];

    public function test_section_headings_come_from_settings(): void
    {
        Setting::putMany(['home_bestsellers_title' => 'Sezonun Seçkisi']);

        $this->get(localized_route('home'))
            ->assertSuccessful()
            ->assertSee('Sezonun Seçkisi');
    }

    public function test_every_language_gets_its_own_heading(): void
    {
        Setting::putMany([
            'home_bestsellers_title' => 'Çok Satanlar',
            'home_bestsellers_title_en' => 'Best Sellers',
            'home_bestsellers_title_bg' => 'Най-продавани',
            'home_bestsellers_title_el' => 'Δημοφιλέστερα',
        ]);

        $expected = [
            'tr' => 'Çok Satanlar',
            'en' => 'Best Sellers',
            'bg' => 'Най-продавани',
            'el' => 'Δημοφιλέστερα',
        ];

        foreach ($expected as $locale => $heading) {
            $this->get(localized_route('home', [], $locale))
                ->assertSuccessful()
                ->assertSee($heading, false);
        }
    }

    public function test_a_missing_translation_falls_back_to_turkish(): void
    {
        Setting::putMany([
            'home_new_title' => 'Yeni Gelenler',
            'home_new_title_en' => '',
        ]);

        $this->get(localized_route('home', [], 'en'))
            ->assertSuccessful()
            ->assertSee('Yeni Gelenler');
    }

    public function test_all_customer_facing_settings_are_localisable(): void
    {
        $seeded = Setting::query()->pluck('key')->all();

        foreach (self::LOCALISED as $key) {
            foreach (Locales::all() as $locale) {
                if ($locale === Locales::default()) {
                    continue;
                }

                $this->assertContains(
                    $key.'_'.$locale,
                    $seeded,
                    "Ayar $key icin $locale karsiligi yok; o dilde Turkce gorunur."
                );
            }
        }
    }

    public function test_the_settings_page_edits_every_language(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'home_deals_title' => 'Fırsat Köşesi',
                'home_deals_title_en' => 'Deal Corner',
            ])
            ->call('save');

        $this->assertSame('Fırsat Köşesi', Setting::get('home_deals_title'));
        $this->assertSame('Deal Corner', Setting::get('home_deals_title_en'));

        $this->get(localized_route('home', [], 'en'))->assertSee('Deal Corner');
    }

    // ------------------------------------------------------- bolum verileri

    public function test_a_section_disappears_when_it_has_no_content(): void
    {
        Slider::query()->delete();
        Banner::query()->delete();

        $html = $this->get(localized_route('home'))->assertSuccessful()->getContent();

        // Slider ve afis bolumleri tamamen cikmali, sayfa yine ayakta kalmali
        $this->assertStringNotContainsString('data-slider', $html);
        $this->assertStringContainsString('section-title', $html);
    }

    public function test_sliders_and_banners_render_from_the_database(): void
    {
        $slider = Slider::query()->first();
        $banner = Banner::query()->where('placement', 'home_mid')->first();

        $this->assertNotNull($slider, 'Panelden yonetilen slider verisi yok.');
        $this->assertNotNull($banner, 'Panelden yonetilen afis verisi yok.');

        $this->get(localized_route('home'))
            ->assertSuccessful()
            ->assertSee($slider->t('title'), false)
            ->assertSee($banner->t('title'), false);
    }
}
