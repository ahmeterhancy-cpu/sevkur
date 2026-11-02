<?php

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * Genc Odasi'ni ust menuye ekler.
 *
 * Menu seridi kategori agacindan degil, menu tablosundan cizilir; kategoriyi
 * acmak onu menude gostermiyor. Kategori acildigi gocte menu ogesi de
 * eklenmezse 104 urunluk oda yalnizca arama ve ic baglantilardan bulunur.
 */
return new class extends Migration
{
    public function up(): void
    {
        $category = Category::where('slug', 'genc-odasi')->first();

        if ($category === null || MenuItem::where('target_slug', 'genc-odasi')->exists()) {
            return;
        }

        // Kategori sirasiyla ayni yere girsin: Calisma Odasi'ndan sonra.
        MenuItem::where('position', '>=', 4)->whereNull('parent_id')->increment('position');

        MenuItem::create([
            'type' => 'category',
            'target_slug' => 'genc-odasi',
            'label' => $category->translations('name'),
            'position' => 4,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        MenuItem::where('target_slug', 'genc-odasi')->delete();
        MenuItem::where('position', '>', 4)->whereNull('parent_id')->decrement('position');
    }
};
