<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // rate = 1 TRY karsiligi. Baslangic degerleri makul kabullerdir;
        // "kur:guncelle" komutu TCMB'den gercek kuru ceker.
        $rows = [
            [
                'code' => 'TRY', 'symbol' => '₺', 'is_base' => true, 'rate' => 1, 'position' => 0,
                'name' => ['tr' => 'Türk Lirası', 'en' => 'Turkish Lira', 'bg' => 'Турска лира', 'el' => 'Τουρκική λίρα'],
            ],
            [
                'code' => 'EUR', 'symbol' => '€', 'is_base' => false, 'rate' => 0.021, 'position' => 1,
                'name' => ['tr' => 'Euro', 'en' => 'Euro', 'bg' => 'Евро', 'el' => 'Ευρώ'],
            ],
            [
                'code' => 'BGN', 'symbol' => 'лв', 'is_base' => false, 'rate' => 0.041, 'position' => 2,
                'name' => ['tr' => 'Bulgar Levası', 'en' => 'Bulgarian Lev', 'bg' => 'Български лев', 'el' => 'Βουλγαρικό λεβ'],
            ],
        ];

        foreach ($rows as $row) {
            Currency::updateOrCreate(['code' => $row['code']], $row + [
                'is_active' => true,
                'auto_update' => $row['code'] !== 'TRY',
                'decimals' => 2,
                'position_symbol' => $row['code'] === 'TRY' ? 'after' : 'after',
                'margin_percent' => 0,
            ]);
        }
    }
}
