<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('kasir_id')->nullable()->after('queue_number')->constrained('users')->nullOnDelete();
            $table->foreignId('waiter_id')->nullable()->after('kasir_id')->constrained('users')->nullOnDelete();
            $table->foreignId('kitchen_id')->nullable()->after('waiter_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['kasir_id']);
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['kitchen_id']);
            $table->dropColumn(['kasir_id', 'waiter_id', 'kitchen_id']);
        });
    }
};

