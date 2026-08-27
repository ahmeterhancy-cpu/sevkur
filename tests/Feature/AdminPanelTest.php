<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\ShopTestCase;

class AdminPanelTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());
    }

    public function test_dashboard_and_settings_render(): void
    {
        $this->get('/admin')->assertSuccessful();
        $this->get('/admin/manage-settings')->assertSuccessful();
    }

    /**
     * Her kaynagin liste ve (varsa) olusturma ekrani hatasiz acilmali.
     * Panel yapilandirmasindaki yazim hatalari burada yakalanir.
     */
    public function test_every_resource_page_renders(): void
    {
        Filament::setCurrentPanel('admin');

        $resources = Filament::getPanel('admin')->getResources();

        $this->assertNotEmpty($resources, 'Panelde hic kaynak bulunamadi');

        foreach ($resources as $resource) {
            $this->get($resource::getUrl('index'))
                ->assertSuccessful();

            if ($resource::canCreate() && array_key_exists('create', $resource::getPages())) {
                $this->get($resource::getUrl('create'))
                    ->assertSuccessful();
            }
        }
    }

    public function test_product_edit_screen_opens(): void
    {
        $product = $this->product(1);

        $this->get(\App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $product]))
            ->assertSuccessful();
    }

    public function test_settings_are_persisted(): void
    {
        \Livewire\Livewire::test(\App\Filament\Pages\ManageSettings::class)
            ->fillForm(['site_name' => 'Sevkur Test'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Sevkur Test', \App\Models\Setting::get('site_name'));
    }
}
