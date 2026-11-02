<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Tests\ShopTestCase;

/**
 * Ana sayfada hangi odanin ray acacagi ve hangisinin bolunmus cizilecegi.
 *
 * Ikisi de kodda sabitti: ray sahibi en cok urunu olan oda, bolunmus duzen
 * hep ortadaki raydi. Magaza "Yemek Odasi'ni one cikaralim" diyemiyordu.
 */
class HomeRailChoiceTest extends ShopTestCase
{
    private function rails()
    {
        return (new \ReflectionMethod(HomeController::class, 'categoryRails'))
            ->invoke(new HomeController);
    }

    private function root(string $slug): Category
    {
        return Category::whereNull('parent_id')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Odanin ilk alt kategorisine urun koyar.
     *
     * Test kurulumu urunleri yalnizca "koltuk-takimlari"na yaziyor; ray
     * secimini sinamak icin baska odalarin da dolu olmasi gerekiyor.
     */
    private function fill(string $rootSlug, int $adet): Category
    {
        $root = $this->root($rootSlug);
        $child = $root->children()->firstOrFail();
        $brand = Brand::firstOrFail();

        foreach (range(1, $adet) as $i) {
            Product::create([
                'category_id' => $child->id,
                'brand_id' => $brand->id,
                'name' => ['tr' => "$rootSlug ürün $i"],
                'slug' => "$rootSlug-urun-$i",
                'price' => 1000,
                'cover' => 'demo/test.jpg',
                'is_active' => true,
                'stock' => 5,
            ]);
        }

        return $root;
    }

    public function test_without_a_choice_the_fullest_rooms_are_picked(): void
    {
        $this->fill('yemek-odasi', 9);
        $this->fill('yatak-odasi', 3);

        Setting::putMany(['home_rails_count' => 2, 'home_rails_min' => 1]);

        $toplamlar = $this->rails()->pluck('toplam')->all();

        $this->assertLessThanOrEqual(2, count($toplamlar));
        $this->assertSame(
            collect($toplamlar)->sortDesc()->values()->all(),
            $toplamlar,
            'Otomatik seçim en dolu odadan başlamalı.'
        );
    }

    public function test_a_ticked_category_takes_over_the_rails(): void
    {
        // Oturma Odasi kurulumda 5 urunle en dolu oda; isaretlenen YEMEK
        // odasi az urunlu olmasina ragmen onun yerine gecmeli.
        $this->fill('yemek-odasi', 2)->update(['show_home_rail' => true]);

        $slugs = $this->rails()->map(fn (array $r) => $r['kategori']->slug)->all();

        $this->assertSame(['yemek-odasi'], $slugs);
    }

    public function test_ticked_categories_follow_the_category_order(): void
    {
        $this->fill('yemek-odasi', 2)->update(['show_home_rail' => true, 'position' => 1]);
        $this->fill('yatak-odasi', 2)->update(['show_home_rail' => true, 'position' => 0]);

        $slugs = $this->rails()->map(fn (array $r) => $r['kategori']->slug)->all();

        $this->assertSame(['yatak-odasi', 'yemek-odasi'], $slugs);
    }

    public function test_the_rail_count_no_longer_truncates_a_manual_choice(): void
    {
        // Yonetici uc oda isaretlediyse "kac ray" sayisi onu kirpmamali;
        // aksi halde isaretledigi oda sessizce kaybolur.
        Setting::putMany(['home_rails_count' => 1]);

        $this->fill('yemek-odasi', 2)->update(['show_home_rail' => true]);
        $this->fill('yatak-odasi', 2)->update(['show_home_rail' => true]);
        $this->root('oturma-odasi')->update(['show_home_rail' => true]);

        $this->assertCount(3, $this->rails());
    }

    public function test_a_ticked_category_ignores_the_minimum_product_rule(): void
    {
        // Az urunlu oda otomatik secimde elenir; yonetici acikca sectiyse
        // elenmemeli, yoksa isaret hicbir ise yaramaz.
        Setting::putMany(['home_rails_min' => 8]);

        $this->fill('yemek-odasi', 2)->update(['show_home_rail' => true]);

        $this->assertSame(['yemek-odasi'], $this->rails()->map(fn (array $r) => $r['kategori']->slug)->all());
    }

    public function test_an_empty_category_never_opens_a_rail(): void
    {
        // Elle isaretlense bile urunu olmayan oda bos ray cizer.
        $bos = Category::whereNull('parent_id')->get()
            ->first(fn (Category $c) => Product::active()
                ->whereIn('category_id', $c->descendantIds())->doesntExist());

        $this->assertNotNull($bos, 'Testin dayandığı boş kategori bulunamadı.');

        $bos->update(['show_home_rail' => true]);

        $this->assertSame([], $this->rails()->map(fn (array $r) => $r['kategori']->slug)->all());
    }

    public function test_the_split_rail_follows_the_setting(): void
    {
        Setting::putMany(['home_rails_min' => 1, 'home_rails_count' => 3, 'home_rail_split' => 1]);

        // Bolunmus duzen 12'lik izgaraya oturur ve odanin fotografini basar.
        $this->get(localized_route('home'))
            ->assertOk()
            ->assertSee('cat-tile--feature', false);
    }

    public function test_the_split_can_be_switched_off(): void
    {
        Setting::putMany(['home_rails_min' => 1, 'home_rails_count' => 3, 'home_rail_split' => 1]);
        $acik = substr_count($this->get(localized_route('home'))->getContent(), 'cat-tile--feature');

        Setting::putMany(['home_rail_split' => 0]);
        $kapali = substr_count($this->get(localized_route('home'))->getContent(), 'cat-tile--feature');

        $this->assertGreaterThan($kapali, $acik, 'Bölünmüş ray kapatılınca oda fotoğrafı düşmeli.');
    }
}
