<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->decimal('harga_promo', 10, 2)->nullable()->after('price');
            $table->boolean('promo_aktif')->default(false)->after('harga_promo');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['harga_promo', 'promo_aktif']);
        });
    }
};

