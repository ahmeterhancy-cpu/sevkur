<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varyant fiyatlamasi ve katalog indeksleri.
 *
 * 1) SECENEK FIYAT KURALLARI
 *    Bir urunun 3 rengi ve 3 olcusu varsa 9 varyant olusur; her birine elle
 *    fiyat girmek hem yorucu hem hataya acik. Bunun yerine SECENEK BASINA fark
 *    girilir ("Kose: +2000", "Kadife: +500") ve varyant fiyati urun fiyati +
 *    seceneklerin farklari olarak HESAPLANIR. 9 satir yerine 2-3 kural.
 *    Yonetici yine de tek bir varyanta elle fiyat yazip kurali ezebilir.
 *
 * 2) INDEKSLER
 *    product_variants'ta hic indeks yoktu; SQLite yabanci anahtar icin indeks
 *    uretmez, yani "bu urunun varyantlari" sorgusu tam tarama yapiyordu.
 *    Katalog buyudukce her urun sayfasinda hissedilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Nitelik ve deger SLUG olarak tutulur: varyantlarin `options`
            // alani da slug tasiyor, ikisi dogrudan eslesiyor.
            $table->string('attribute_slug', 60);
            $table->string('value_slug', 60);

            // Urun fiyatina eklenecek fark; eksi de olabilir (indirimli secenek)
            $table->decimal('price_delta', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'attribute_slug', 'value_slug'], 'urun_secenek_benzersiz');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'is_active', 'position'], 'varyant_urun_sira');
            $table->index('sku', 'varyant_sku');
        });

        Schema::table('products', function (Blueprint $table) {
            // scopeActive: is_active + published_at
            $table->index(['is_active', 'published_at'], 'urun_yayin');
            // Magazadaki siralamalar
            $table->index(['is_active', 'price'], 'urun_fiyat');
            $table->index(['is_active', 'sold_count'], 'urun_satis');
        });

        Schema::table('attribute_value_product', function (Blueprint $table) {
            // Ozellik suzgeci degerden urune dogru arar; birlesik birincil
            // anahtar bu yonu KAPSAMAZ.
            $table->index('attribute_value_id', 'ozellik_deger_urun');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_value_product', function (Blueprint $table) {
            $table->dropIndex('ozellik_deger_urun');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('urun_yayin');
            $table->dropIndex('urun_fiyat');
            $table->dropIndex('urun_satis');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('varyant_urun_sira');
            $table->dropIndex('varyant_sku');
        });

        Schema::dropIfExists('product_option_prices');
    }
};
