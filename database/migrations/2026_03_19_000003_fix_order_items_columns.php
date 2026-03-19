<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'menu_item_id')) {
                $table->foreignId('menu_item_id')->nullable()->after('order_id')->constrained('menu_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('order_items', 'note')) {
                $table->text('note')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['menu_item_id']);
            $table->dropColumn(['menu_item_id', 'note']);
        });
    }
};
