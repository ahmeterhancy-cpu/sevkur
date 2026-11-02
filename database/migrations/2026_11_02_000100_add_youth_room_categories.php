<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

/**
 * Genc Odasi kategori agaci.
 *
 * Arno'nun "arno-go" hattindaki 104 urun genc odasi mobilyasi; Sevkur'da
 * karsiligi olmadigi icin yetiskin kategorilerine dusuyorlardi (genc dolap
 * -> Gardiroplar). Islevsel olarak dogruydu ama musteri genc odasi arayinca
 * 65 gardirobun icinde 22 genc dolabi ayiklamak zorunda kaliyordu.
 *
 * Urunleri bu kategorilere tasiyan komut: kategori:yeniden-esle arno
 */
return new class extends Migration
{
    private const PARENT = ['Genç Odası', 'Youth Room', 'Юношеска стая', 'Εφηβικό δωμάτιο'];

    /** slug => [tr, en, bg, el] */
    private const CHILDREN = [
        'genc-odasi-takimlari' => ['Genç Odası Takımları', 'Youth Room Sets', 'Комплекти юношеска стая', 'Σετ εφηβικού δωματίου'],
        'genc-karyolalar' => ['Genç Karyolalar', 'Youth Beds', 'Юношески легла', 'Εφηβικά κρεβάτια'],
        'ranzalar' => ['Ranzalar', 'Bunk Beds', 'Двуетажни легла', 'Κουκέτες'],
        'genc-gardiroplar' => ['Genç Gardıroplar', 'Youth Wardrobes', 'Юношески гардероби', 'Εφηβικές ντουλάπες'],
        'genc-calisma-masalari' => ['Genç Çalışma Masaları', 'Youth Desks', 'Юношески бюра', 'Εφηβικά γραφεία'],
        'genc-komodinler' => ['Genç Komodinler', 'Youth Nightstands', 'Юношески нощни шкафчета', 'Εφηβικά κομοδίνα'],
        'genc-sifonyerler' => ['Genç Şifonyerler', 'Youth Dressers', 'Юношески скринове', 'Εφηβικές συρταριέρες'],
        'genc-kitapliklar' => ['Genç Kitaplıklar', 'Youth Bookcases', 'Юношески библиотеки', 'Εφηβικές βιβλιοθήκες'],
    ];

    /** Genc Odasi oda kategorilerinin arasina girsin, dekorasyonun yanina degil. */
    private const POSITION = 4;

    public function up(): void
    {
        // Aydinlatma ve sonrasi bir basamak kaysin ki sira acilsin.
        Category::whereNull('parent_id')
            ->where('position', '>=', self::POSITION)
            ->increment('position');

        [$tr, $en, $bg, $el] = self::PARENT;

        $parent = Category::updateOrCreate(['slug' => 'genc-odasi'], [
            'parent_id' => null,
            'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
            'position' => self::POSITION,
            'is_active' => true,
            'is_featured' => false,
            'show_in_menu' => true,
        ]);

        $position = 0;

        foreach (self::CHILDREN as $slug => [$tr, $en, $bg, $el]) {
            Category::updateOrCreate(['slug' => $slug], [
                'parent_id' => $parent->id,
                'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                'position' => $position,
                'is_active' => true,
                'is_featured' => false,
                'show_in_menu' => true,
            ]);

            $position += 10;
        }
    }

    public function down(): void
    {
        // Urunlerin category_id'si nullOnDelete: kategori gidince urun kalir.
        Category::whereIn('slug', array_keys(self::CHILDREN))->delete();
        Category::where('slug', 'genc-odasi')->delete();

        Category::whereNull('parent_id')
            ->where('position', '>', self::POSITION)
            ->decrement('position');
    }
};
