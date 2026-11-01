<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Import\ProductIdentity;
use Tests\ShopTestCase;

/**
 * Aktarilan urunun kimligi.
 *
 * Slug ile eslestirmek veri kaybettirdi: Sude aktariminda "Armani Koltuk
 * Takimi" Vespero'nun ayni adli urununu devraldi. Tedarikci siteleri ayni
 * model adlarini kullaniyor, carpisma kural.
 */
class ImportIdentityTest extends ShopTestCase
{
    private function identity(): ProductIdentity
    {
        return app(ProductIdentity::class);
    }

    public function test_a_free_name_keeps_its_plain_slug(): void
    {
        $this->assertSame(
            'bambaska-bir-koltuk',
            $this->identity()->slug('Bambaşka Bir Koltuk', 'sude')
        );
    }

    public function test_a_name_taken_by_another_brand_gets_the_brand_suffix(): void
    {
        $taken = $this->product(1);
        $taken->update(['slug' => 'armani-koltuk-takimi']);

        $this->assertSame(
            'armani-koltuk-takimi-sude',
            $this->identity()->slug('Armani Koltuk Takımı', 'sude')
        );
    }

    public function test_it_keeps_counting_when_the_suffix_is_taken_too(): void
    {
        $first = $this->product(1);
        $first->update(['slug' => 'armani-koltuk-takimi']);

        $second = $this->product(2);
        $second->update(['slug' => 'armani-koltuk-takimi-sude']);

        $this->assertSame(
            'armani-koltuk-takimi-sude-2',
            $this->identity()->slug('Armani Koltuk Takımı', 'sude')
        );
    }

    public function test_a_product_keeps_its_own_slug_on_re_import(): void
    {
        $product = $this->product(1);
        $product->update(['slug' => 'armani-koltuk-takimi']);

        $this->assertSame(
            'armani-koltuk-takimi',
            $this->identity()->slug('Armani Koltuk Takımı', 'sude', $product->id)
        );
    }

    public function test_the_source_url_finds_the_product_again(): void
    {
        $url = 'https://www.sudecollection.com/oscar-koltuk-takimi/';

        $product = $this->product(1);
        $product->update(['meta' => ['kaynak_adres' => $url]]);

        $this->assertSame($product->id, $this->identity()->existing($url)?->id);
        $this->assertNull($this->identity()->existing($url.'baska/'));
    }

    public function test_two_brands_with_the_same_model_name_both_survive(): void
    {
        $vespero = Brand::create(['slug' => 'vespero', 'name' => ['tr' => 'Vespero'], 'is_active' => true]);
        $sude = Brand::create(['slug' => 'sude', 'name' => ['tr' => 'Sude'], 'is_active' => true]);

        $a = $this->product(1);
        $a->update([
            'brand_id' => $vespero->id,
            'slug' => $this->identity()->slug('Armani Koltuk Takımı', 'vespero'),
        ]);

        $b = $this->product(2);
        $b->update([
            'brand_id' => $sude->id,
            'slug' => $this->identity()->slug('Armani Koltuk Takımı', 'sude'),
        ]);

        $this->assertNotSame($a->fresh()->slug, $b->fresh()->slug);
        $this->assertSame(2, Product::whereIn('id', [$a->id, $b->id])->count());
    }
}
