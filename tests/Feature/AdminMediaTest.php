<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\ShopTestCase;

/**
 * Panelde gorsel alanlari.
 *
 * Filament FileUpload TEK bir disk bilir. Disk yanlis ayarlanirsa yonetici
 * mevcut gorseli goremez ve kaydettiginde alan bos gidip gorsel SILINIR.
 * Bu testler o sessiz veri kaybini yakalar.
 */
class AdminMediaTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::where('email', 'admin@sevkurmobilya.com')->firstOrFail());
    }

    public function test_filament_uses_the_same_disk_the_storefront_serves_from(): void
    {
        $this->assertSame('medya', config('filament.default_filesystem_disk'));
    }

    public function test_every_product_image_is_readable_on_the_filament_disk(): void
    {
        $disk = Storage::disk(config('filament.default_filesystem_disk'));

        $missing = [];

        foreach (Product::with('images')->get() as $product) {
            $paths = array_filter([
                $product->getRawOriginal('cover'),
                $product->getRawOriginal('hover_image'),
                ...$product->images->map(fn ($i) => $i->getRawOriginal('path'))->all(),
            ]);

            foreach ($paths as $path) {
                if (! $disk->exists($path)) {
                    $missing[] = $product->slug.' → '.$path;
                }
            }
        }

        $this->assertSame([], $missing, 'Panelde gorunmeyecek gorseller var: '.implode(', ', $missing));
    }

    public function test_the_upload_field_hands_the_existing_file_to_the_browser(): void
    {
        // Panelde onizlemenin dolmasi bu cagrinin dosyayi bulmasina baglidir.
        $product = Product::whereNotNull('cover')->firstOrFail();

        $files = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->call('callSchemaComponentMethod', 'form.cover', 'getUploadedFiles')
            ->effects['returns'][0] ?? null;

        $this->assertNotEmpty($files, 'Kapak gorseli panelde bos gorunuyor.');
        $this->assertNotNull(reset($files), 'Dosya diskte bulunamadi.');
    }

    public function test_saving_a_product_without_touching_images_keeps_them(): void
    {
        $product = Product::whereNotNull('cover')->firstOrFail();
        $cover = $product->getRawOriginal('cover');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            $cover,
            $product->fresh()->getRawOriginal('cover'),
            'Kaydetmek kapak gorselini sildi.'
        );
    }
}
