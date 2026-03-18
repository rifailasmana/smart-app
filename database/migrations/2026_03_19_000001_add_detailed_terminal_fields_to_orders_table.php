<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_category', 32)->default('REGULER')->after('category');
            $table->string('order_type', 16)->default('DINE_IN')->after('guest_category'); // DINE_IN, TAKE_AWAY
            $table->string('reservation_code', 64)->nullable()->after('order_type');
            $table->string('reservation_name', 128)->nullable()->after('reservation_code');
            $table->boolean('is_split_bill')->default(false)->after('reservation_name');
            $table->string('merged_table_ids')->nullable()->after('is_split_bill'); // JSON array of table IDs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'guest_category',
                'order_type',
                'reservation_code',
                'reservation_name',
                'is_split_bill',
                'merged_table_ids'
            ]);
        });
    }
};
