<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bulgar Levasi (BGN) vitrinden kaldirilir; Bulgarca artik euro gosterir.
 *
 * Tahsilat her zaman TRY oldugu icin bu yalnizca gosterimi ilgilendirir.
 * Levayi secmis eski siparislerin `display_currency` degeri euroya cevrilir:
 * kayit silinince o siparislerin para birimi cozulemez hale gelirdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('display_currency', 'BGN')->update(['display_currency' => 'EUR']);

        DB::table('currencies')->where('code', 'BGN')->delete();
    }

    public function down(): void
    {
        // Leva'yi geri ekle. Kur, euro para kurulu sabitinden turetilir
        // (1 EUR = 1.95583 BGN); TCMB leva yayinlamaz.
        $euro = DB::table('currencies')->where('code', 'EUR')->first();

        DB::table('currencies')->updateOrInsert(
            ['code' => 'BGN'],
            [
                'symbol' => 'лв',
                'name' => json_encode([
                    'tr' => 'Bulgar Levası',
                    'en' => 'Bulgarian Lev',
                    'bg' => 'Български лев',
                    'el' => 'Βουλγαρικό λεβ',
                ], JSON_UNESCAPED_UNICODE),
                'rate' => $euro ? round($euro->rate * 1.95583, 6) : 0.041,
                'is_base' => false,
                'is_active' => true,
                'auto_update' => true,
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
