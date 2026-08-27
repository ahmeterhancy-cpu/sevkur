<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo magaza. Tum degerler deterministiktir; tekrar calistirilabilir.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            CurrencySeeder::class,
            TaxonomySeeder::class,
            ProductSeeder::class,
            ContentSeeder::class,
            ShopSeeder::class,
        ]);
    }
}
