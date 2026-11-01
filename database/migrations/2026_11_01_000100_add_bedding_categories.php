<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

/**
 * Yatak, baza ve baslik kategorileri.
 *
 * Ravizza bir YATAK markasi; katalogunda yatak, yatak seti, baza ve yatak
 * basligi var. Yatak Odasi altinda bunlarin karsiligi yoktu (Karyolalar
 * karyola iskeletidir, yatagin kendisi degil) ve urunler yerine
 * konulamiyordu.
 */
return new class extends Migration
{
    /** slug => [tr, en, bg, el] */
    private const CATEGORIES = [
        'yataklar' => ['Yataklar', 'Mattresses', 'Матраци', 'Στρώματα'],
        'yatak-setleri' => ['Yatak Setleri', 'Mattress Sets', 'Комплекти матраци', 'Σετ στρωμάτων'],
        'bazalar' => ['Bazalar', 'Bed Bases', 'Основи за легло', 'Βάσεις κρεβατιού'],
        'yatak-basliklari' => ['Yatak Başlıkları', 'Headboards', 'Табли за легло', 'Κεφαλάρια'],
    ];

    public function up(): void
    {
        $parent = Category::where('slug', 'yatak-odasi')->first();

        if ($parent === null) {
            return;
        }

        // Mevcut alt kategorilerin ardina dizilsinler.
        $position = (int) Category::where('parent_id', $parent->id)->max('position');

        foreach (self::CATEGORIES as $slug => [$tr, $en, $bg, $el]) {
            $position += 10;

            Category::updateOrCreate(['slug' => $slug], [
                'parent_id' => $parent->id,
                'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                'position' => $position,
                'is_active' => true,
                'is_featured' => false,
                'show_in_menu' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Urunlerin category_id'si nullOnDelete: kategori gidince urun kalir.
        Category::whereIn('slug', array_keys(self::CATEGORIES))->delete();
    }
};
