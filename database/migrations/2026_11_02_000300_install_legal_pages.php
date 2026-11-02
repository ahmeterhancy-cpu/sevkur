<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Yasal metinleri kurar.
 *
 * Mesafeli satis sozlesmesi ve gizlilik politikasi kurulumdan beri "bu metin
 * ornek icerik" diyen birer paragraftan ibaretti; KVKK aydinlatma metni ve
 * cerez politikasi ise hic yoktu. Ikisi de Turkiye'de satis yapan bir
 * e-ticaret sitesi icin zorunlu.
 *
 * Metinler database/data/legal/ altinda; sirket bilgileri [[...]] isaretiyle
 * ayarlardan doldurulur (legal_fill()).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pages = require database_path('data/legal-pages.php');

        // Yasal sayfalar alt bilgide en sona dizilsin; "Hakkimizda" ve
        // "Teslimat" gibi sayfalarin onune gecmesinler.
        $position = max(100, (int) Page::max('position') + 10);

        foreach ($pages as $page) {
            $mevcut = Page::where('slug', $page['slug'])->first();

            Page::updateOrCreate(['slug' => $page['slug']], [
                'title' => $page['title'],
                'body' => $page['body'],
                'is_active' => true,
                'show_in_footer' => $page['footer'],
                // Var olan sayfanin yerini bozma; yalnizca yenilere sira ver.
                'position' => $mevcut?->position ?? $position += 10,
            ]);
        }
    }

    public function down(): void
    {
        // Eski yer tutucu metinlere donmek anlamli degil; yalnizca bu gocle
        // EKLENEN iki sayfa kaldirilir.
        Page::whereIn('slug', ['kvkk-aydinlatma-metni', 'cerez-politikasi'])->delete();
    }
};
