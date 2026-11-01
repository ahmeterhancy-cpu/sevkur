<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\ShopTestCase;

/**
 * Ana sayfadaki "Cok Satanlar" bolumu.
 *
 * Katalog disaridan aktarildiginda butun urunlerin sold_count\'u 0 olur.
 * O durumda siralama "en son eklenenler"e dusup Yeni Gelenler bolumunun
 * aynisini gosteriyordu — ayni on urun iki kez. Ustelik hic satilmamis
 * urunu "cok satan" diye etiketlemek musteriye yanlis bilgi.
 */
class HomeBestSellersTest extends ShopTestCase
{
    public function test_the_section_is_hidden_when_nothing_has_sold(): void
    {
        Product::query()->update(['sold_count' => 0]);

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertDontSee('Çok Satanlar', false);
    }

    public function test_it_shows_only_products_that_actually_sold(): void
    {
        Product::query()->update(['sold_count' => 0]);

        $sold = Product::active()->firstOrFail();
        $sold->update(['sold_count' => 12]);

        $other = Product::active()->where('id', '!=', $sold->id)->firstOrFail();

        $html = $this->get(localized_route('home'))->assertOk()->getContent();
        $section = $this->bestSellerSection($html);

        // Satan urun bolumde; satmayan urun bolumde DEGIL.
        $this->assertStringContainsString($sold->t('name'), $section);
        $this->assertStringNotContainsString($other->t('name'), $section);
    }

    public function test_best_sellers_and_new_arrivals_are_not_the_same_list(): void
    {
        Product::query()->update(['sold_count' => 0, 'is_new' => false]);

        $html = $this->get(localized_route('home'))->assertOk()->getContent();

        // Cok Satanlar gizli, Yeni Gelenler dolu: ayni liste iki kez cikmaz.
        $this->assertStringNotContainsString('Çok Satanlar', $html);
        $this->assertStringContainsString('Yeni Gelenler', $html);
    }

    /** "Cok Satanlar" basligindan sonraki bolumun HTML'i. */
    private function bestSellerSection(string $html): string
    {
        $start = mb_strpos($html, 'Çok Satanlar');
        $this->assertNotFalse($start, 'Çok Satanlar bölümü bulunamadı.');

        $next = mb_strpos($html, 'Yeni Gelenler', $start);

        return mb_substr($html, $start, $next === false ? null : $next - $start);
    }
}
