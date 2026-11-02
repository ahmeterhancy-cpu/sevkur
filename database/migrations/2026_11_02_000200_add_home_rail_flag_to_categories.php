<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ana sayfada hangi odanin ray acacagi.
 *
 * Secim urun sayisina gore kendiliginden yapiliyordu: magaza "Yemek Odasi'ni
 * one cikaralim" diyemiyordu, cunku urun sayisi en fazla olan kazaniyordu.
 * Isaretli kategori yoksa eski otomatik davranis surdugu icin mevcut
 * kurulumlar degismeden calismaya devam eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_home_rail')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_home_rail');
        });
    }
};
