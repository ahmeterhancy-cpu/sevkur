<?php

namespace Tests\Feature;

use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Baslik menusu.
 *
 * Ana kategoriler menu seridinde, alt kategorileri acilir menude durur.
 * Alt menu kategoriden CANLI okunur: yonetici yeni bir alt kategori
 * actiginda menuyu ayrica duzenlemesi gerekmez.
 */
class NavigationTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MenuItem::forgetCategoryIndex();
    }

    public function test_the_menu_lists_the_top_level_categories(): void
    {
        $items = MenuItem::active()->where('location', 'header')->get();

        $this->assertNotEmpty($items);

        $categories = Category::whereNull('parent_id')->where('show_in_menu', true)->pluck('slug');

        foreach ($categories as $slug) {
            $this->assertTrue(
                $items->contains(fn (MenuItem $item) => $item->type === 'category' && $item->target_slug === $slug),
                "Menude $slug kategorisi yok."
            );
        }
    }

    public function test_a_category_item_offers_its_children_as_a_submenu(): void
    {
        $item = MenuItem::where('type', 'category')->firstOrFail();
        $category = Category::where('slug', $item->target_slug)->firstOrFail();

        $submenu = $item->submenu();

        $this->assertCount($category->activeChildren->count(), $submenu);
        $this->assertSame($category->activeChildren->first()->t('name'), $submenu[0]['label']);
        $this->assertStringContainsString($category->activeChildren->first()->slug, $submenu[0]['href']);
    }

    public function test_a_new_subcategory_appears_without_touching_the_menu(): void
    {
        $item = MenuItem::where('type', 'category')->firstOrFail();
        $parent = Category::where('slug', $item->target_slug)->firstOrFail();
        $before = count($item->submenu());

        Category::create([
            'parent_id' => $parent->id,
            'name' => ['tr' => 'Puflar', 'en' => 'Poufs'],
            'slug' => 'puflar',
            'is_active' => true,
            'position' => 99,
        ]);

        MenuItem::forgetCategoryIndex();

        $fresh = MenuItem::find($item->id);

        $this->assertCount($before + 1, $fresh->submenu());
        $this->assertContains('Puflar', array_column($fresh->submenu(), 'label'));
    }

    public function test_the_header_renders_categories_with_their_children(): void
    {
        $item = MenuItem::where('type', 'category')->firstOrFail();
        $category = Category::where('slug', $item->target_slug)->firstOrFail();
        $child = $category->activeChildren->first();

        $this->get(localized_route('home'))
            ->assertSuccessful()
            ->assertSee($category->t('name'), false)
            ->assertSee($child->t('name'), false)
            ->assertSee('has-drop', false);
    }

    public function test_a_category_appears_only_once_in_the_mobile_panel(): void
    {
        $item = MenuItem::where('type', 'category')->firstOrFail();
        $category = Category::where('slug', $item->target_slug)->firstOrFail();
        $href = localized_route('category', ['category' => $category->slug]);

        // Ana sayfada ayrica "one cikan kategoriler" vitrini var; sayim
        // karismasin diye yalnizca baslik ve alt bilginin oldugu bir sayfa.
        $html = $this->get(localized_route('contact'))->assertSuccessful()->getContent();

        // Masaustu seridi + mobil panel = iki kez. Ucuncusu, kaldirilan
        // "tum kategoriler" listesinin geri geldigi anlamina gelir.
        $this->assertSame(
            2,
            substr_count($html, 'href="'.$href.'"'),
            'Kategori baglantisi beklenenden fazla tekrarliyor.'
        );
    }

    public function test_the_menu_costs_one_category_query_no_matter_how_many_items(): void
    {
        $items = MenuItem::active()->where('location', 'header')->get();
        $this->assertGreaterThan(3, $items->count(), 'Test anlamli olsun diye birkac menu ogesi gerekli.');

        MenuItem::forgetCategoryIndex();

        DB::enableQueryLog();

        foreach ($items as $item) {
            $item->submenu();
        }

        $queries = collect(DB::getRawQueryLog())
            ->filter(fn (array $q) => str_contains($q['raw_query'], 'categories'))
            ->count();

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(2, $queries, 'Her menu ogesi icin ayri kategori sorgusu aciliyor.');
    }

    public function test_the_menu_can_be_edited_from_the_admin_panel(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $item = MenuItem::where('type', 'category')->orderBy('position')->firstOrFail();

        Livewire::test(EditMenuItem::class, [
            'record' => $item->getKey(),
        ])->fillForm([
            'label' => ['tr' => 'Salon', 'en' => 'Living'],
        ])->call('save')->assertHasNoFormErrors();

        MenuItem::forgetCategoryIndex();

        $this->get(localized_route('home'))->assertSuccessful()->assertSee('Salon', false);
    }

    public function test_choosing_a_category_fills_the_label_in_every_language(): void
    {
        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());

        $category = Category::whereNull('parent_id')->orderBy('position')->firstOrFail();

        $state = Livewire::test(CreateMenuItem::class)
            ->set('data.type', 'category')
            ->set('data.target_slug', $category->slug)
            ->get('data');

        // Yonetici etiketi dort dile elle yazmak zorunda kalmasin
        $this->assertSame($category->t('name', 'tr'), $state['label']['tr'] ?? null);
        $this->assertSame($category->t('name', 'en'), $state['label']['en'] ?? null);
    }

    public function test_hiding_a_menu_item_removes_it_from_the_storefront(): void
    {
        $item = MenuItem::where('type', 'category')->orderBy('position')->firstOrFail();
        $label = $item->t('label');

        $item->update(['is_active' => false]);
        MenuItem::forgetCategoryIndex();

        $html = $this->get(localized_route('contact'))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('>'.$label.'<', $html);
    }

    public function test_a_broken_category_link_does_not_break_the_page(): void
    {
        MenuItem::create([
            'location' => 'header',
            'label' => ['tr' => 'Silinmiş'],
            'type' => 'category',
            'target_slug' => 'olmayan-kategori',
            'position' => 99,
            'is_active' => true,
        ]);

        MenuItem::forgetCategoryIndex();

        $this->get(localized_route('home'))->assertSuccessful();
    }
}
