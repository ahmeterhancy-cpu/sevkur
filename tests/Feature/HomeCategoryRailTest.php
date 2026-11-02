<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Product;
use Tests\ShopTestCase;

/**
 * Ana sayfadaki oda raylari.
 *
 * Katalogda 600'u askin urun varken ana sayfa yalnizca "Yeni Gelenler"
 * rayini gosteriyordu; fiyat girilmedigi icin "Cok Satanlar" ve "Firsatlar"
 * bolumleri de bos kaldigindan sayfa ince duruyordu.
 */
class HomeCategoryRailTest extends ShopTestCase
{
    /** @return array<int, array<string, mixed>> */
    private function rails(): array
    {
        $method = new \ReflectionMethod(HomeController::class, 'categoryRails');

        return $method->invoke(new HomeController)->all();
    }

    /**
     * Bir oda kategorisinin alt kategorilerini urunle doldurur.
     *
     * Tohum verisinde ray acacak kadar urun yok; test kendi katalogunu kurar.
     * Her alt kategori AYRI bir seri adi alir ki seri cesitliligi sinanabilsin.
     */
    private function fillRoom(string $roomSlug, int $perChild = 3): Category
    {
        $room = Category::where('slug', $roomSlug)->firstOrFail();
        $sample = Product::whereNotNull('cover')->firstOrFail();

        foreach ($room->children as $index => $child) {
            for ($i = 0; $i < $perChild; $i++) {
                Product::create([
                    'category_id' => $child->id,
                    'slug' => $child->slug.'-ray-testi-'.$i,
                    'name' => ['tr' => 'Seri'.$index.' Ürün '.$i],
                    'price' => 1000,
                    'stock' => 5,
                    'cover' => $sample->cover,
                    'is_active' => true,
                    'published_at' => now(),
                ]);
            }
        }

        return $room->fresh();
    }

    /** @return array<string, mixed>|null */
    private function railFor(Category $room): ?array
    {
        return collect($this->rails())
            ->firstWhere(fn (array $rail) => $rail['kategori']->id === $room->id);
    }

    public function test_a_room_rail_spreads_across_its_subcategories(): void
    {
        $rail = $this->railFor($this->fillRoom('oturma-odasi'));

        $this->assertNotNull($rail, 'Dolu oda için ray açılmadı.');

        // Tek alt kategoriden on urun odayi temsil etmez.
        $this->assertGreaterThan(
            1,
            $rail['urunler']->pluck('category_id')->unique()->count()
        );
    }

    public function test_a_rail_does_not_repeat_the_same_series_back_to_back(): void
    {
        $rail = $this->railFor($this->fillRoom('oturma-odasi'));

        $series = $rail['urunler']->map(
            fn (Product $p) => mb_strtolower(explode(' ', trim($p->t('name')))[0], 'UTF-8')
        );

        // Aktarim seriyi toplu yazdigi icin her alt kategorinin en yenisi ayni
        // seridendi: ray "Vera Başlık, Vera Baza, Vera Yatak…" diye diziliyordu.
        $previous = null;

        foreach ($series as $name) {
            $this->assertNotSame($previous, $name, '"'.$name.'" art arda tekrar ediyor.');
            $previous = $name;
        }
    }

    public function test_a_thin_category_does_not_open_a_rail(): void
    {
        $this->fillRoom('oturma-odasi');

        foreach ($this->rails() as $rail) {
            // Yari bos ray kotu durur.
            $this->assertGreaterThanOrEqual(8, $rail['toplam']);
        }
    }

    public function test_the_rail_appears_on_the_home_page(): void
    {
        $room = $this->fillRoom('oturma-odasi');

        $this->get(localized_route('home'))
            ->assertOk()
            ->assertSee($room->t('name'), false);
    }

    public function test_an_empty_catalogue_produces_no_rails(): void
    {
        Product::query()->update(['is_active' => false]);

        $this->assertSame([], $this->rails());

        // Sayfa yine ayakta kalmali.
        $this->get(localized_route('home'))->assertOk();
    }
}
