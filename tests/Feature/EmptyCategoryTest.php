<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\ShopTestCase;

/**
 * Urunu olmayan kategoriler.
 *
 * Katalog gercek urunlerle dolunca Aydinlatma ve Ev Tekstili bos kaldi.
 * Vitrindeki kartlari hem bos sayfaya goturuyordu hem de gosterecek urun
 * fotografi olmadigi icin kurulumdan kalma demo gorseli (antika terazi,
 * muzelik kilim) tasiyordu.
 */
class EmptyCategoryTest extends ShopTestCase
{
    public function test_an_empty_category_is_not_shown_on_the_home_page(): void
    {
        $empty = Category::active()->where('is_featured', true)->firstOrFail();

        // Kategoriyi ve butun alt kategorilerini bosalt
        Product::whereIn('category_id', $empty->descendantIds())->delete();

        $html = $this->get(localized_route('home'))->assertOk()->getContent();

        // Kategori adi ust menude de geciyor; kontrol VITRIN KUTUSUNA bagli.
        $this->assertStringNotContainsString(
            'class="cat-tile__name">'.$empty->t('name').'</h3>',
            $html
        );
    }

    public function test_a_category_with_products_is_still_shown(): void
    {
        $category = Category::active()->where('is_featured', true)->firstOrFail();

        $this->assertGreaterThan(
            0,
            Product::active()->whereIn('category_id', $category->descendantIds())->count(),
            'Hazırlık verisinde ürünlü kategori yok.'
        );

        $html = $this->get(localized_route('home'))->assertOk()->getContent();

        $this->assertStringContainsString(
            'class="cat-tile__name">'.$category->t('name').'</h3>',
            $html
        );
    }
}
