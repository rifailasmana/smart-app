<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Detailed Audit Trail Timestamps
            if (!Schema::hasColumn('orders', 'ordered_at')) {
                $table->timestamp('ordered_at')->nullable()->after('stage');
            }
            if (!Schema::hasColumn('orders', 'cooking_at')) {
                $table->timestamp('cooking_at')->nullable()->after('sent_to_kitchen_at');
            }
            if (!Schema::hasColumn('orders', 'served_at')) {
                $table->timestamp('served_at')->nullable()->after('kitchen_done_at');
            }

            // Detailed Staff Logs (Already have kasir_id, waiter_id, kitchen_id)
            // We ensure they are named correctly for our audit logic
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['ordered_at', 'cooking_at', 'served_at']);
        });
    }
};
