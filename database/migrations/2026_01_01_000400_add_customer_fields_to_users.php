<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->string('locale', 5)->default('tr')->after('phone');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->boolean('accepts_marketing')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('accepts_marketing');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'locale', 'is_active', 'accepts_marketing', 'last_login_at']);
        });
    }
};
