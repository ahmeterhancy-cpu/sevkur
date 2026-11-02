<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Support\Locales;
use Tests\ShopTestCase;

/**
 * Yasal metinler.
 *
 * Mesafeli satis sozlesmesi, KVKK aydinlatma metni, cerez politikasi ve
 * gizlilik politikasi Turkiye'de satis yapan bir e-ticaret sitesi icin
 * zorunlu; sayfalar silinirse veya sirket bilgileri eksik kalirsa bu
 * testler uyarir.
 */
class LegalPagesTest extends ShopTestCase
{
    /** @var array<int, string> */
    private const SLUGS = [
        'mesafeli-satis-sozlesmesi',
        'kvkk-aydinlatma-metni',
        'cerez-politikasi',
        'gizlilik-politikasi',
        'iade-ve-degisim',
    ];

    public function test_every_legal_page_is_published_and_in_the_footer(): void
    {
        foreach (self::SLUGS as $slug) {
            $page = Page::where('slug', $slug)->first();

            $this->assertNotNull($page, "Eksik yasal sayfa: $slug");
            $this->assertTrue($page->is_active, "$slug yayında değil.");
            $this->assertTrue($page->show_in_footer, "$slug alt bilgide görünmüyor.");
        }
    }

    public function test_every_legal_page_opens_in_every_language(): void
    {
        foreach (self::SLUGS as $slug) {
            foreach (Locales::all() as $locale) {
                $this->get(localized_route('page', ['page' => $slug], $locale))
                    ->assertOk();
            }
        }
    }

    public function test_each_language_has_its_own_text(): void
    {
        foreach (self::SLUGS as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();

            foreach (Locales::all() as $locale) {
                $this->assertNotSame(
                    '',
                    trim($page->translations('body')[$locale] ?? ''),
                    "$slug — $locale çevirisi boş."
                );
            }
        }
    }

    public function test_company_details_are_filled_in_from_the_settings(): void
    {
        Setting::putMany([
            'legal_company_name' => 'Sevkur Mobilya San. ve Tic. Ltd. Şti.',
            'legal_mersis' => '0123456789012345',
        ]);

        $html = $this->get(localized_route('page', ['page' => 'mesafeli-satis-sozlesmesi']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sevkur Mobilya San. ve Tic. Ltd. Şti.', $html);
        $this->assertStringContainsString('0123456789012345', $html);
        $this->assertStringNotContainsString('[[unvan]]', $html, 'İşaret doldurulmadan sayfaya düşmüş.');
    }

    public function test_a_missing_detail_is_visible_rather_than_silently_blank(): void
    {
        // Eksik bilgiyle yayina cikilirsa sozlesmede bos bir satir degil,
        // gozden kacmayacak bir uyari dursun.
        Setting::putMany(['legal_mersis' => '']);

        $this->get(localized_route('page', ['page' => 'mesafeli-satis-sozlesmesi']))
            ->assertOk()
            ->assertSee('[MERSİS numarası girilmedi]');
    }

    public function test_the_marks_only_apply_to_legal_text(): void
    {
        // legal_fill() her sayfada calisiyor; isaret icermeyen metni
        // degistirmemeli.
        $this->assertSame('Masif ahşap [not a mark] 14 gün', legal_fill('Masif ahşap [not a mark] 14 gün'));
        $this->assertSame('[[bilinmeyen]]', legal_fill('[[bilinmeyen]]'));
    }

    public function test_the_withdrawal_period_matches_across_the_two_pages(): void
    {
        // Iade sayfasi ile sozlesmenin cayma suresi AYNI olmali; biri
        // degistirilip digeri unutulursa musteriye celisen bilgi verilir.
        foreach (['mesafeli-satis-sozlesmesi', 'iade-ve-degisim'] as $slug) {
            $body = Page::where('slug', $slug)->firstOrFail()->translations('body')['tr'];

            $this->assertStringContainsString('14', $body);
            $this->assertStringContainsString('cay', mb_strtolower($body, 'UTF-8'));
        }
    }
}
