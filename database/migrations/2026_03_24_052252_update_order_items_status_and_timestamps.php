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
        Schema::table('order_items', function (Blueprint $table) {
            // Update status to support: pending, cooking, ready, served, void
            // Using string for flexibility
            if (Schema::hasColumn('order_items', 'status')) {
                $table->string('status', 32)->default('pending')->change();
            } else {
                $table->string('status', 32)->default('pending');
            }
            
            $table->timestamp('cooking_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('void_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['cooking_at', 'ready_at', 'served_at', 'void_at']);
        });
    }
};
