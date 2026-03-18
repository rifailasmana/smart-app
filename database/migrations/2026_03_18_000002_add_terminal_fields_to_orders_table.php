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
            $table->string('stage', 32)->default('DRAFT')->after('status');
            $table->timestamp('submitted_to_cashier_at')->nullable()->after('stage');
            $table->timestamp('paid_at')->nullable()->after('submitted_to_cashier_at');
            $table->timestamp('sent_to_kitchen_at')->nullable()->after('paid_at');
            $table->timestamp('kitchen_done_at')->nullable()->after('sent_to_kitchen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cashier_user_id']);
            $table->dropColumn([
                'stage',
                'submitted_to_cashier_at',
                'cashier_user_id',
                'paid_at',
                'sent_to_kitchen_at',
                'kitchen_done_at'
            ]);
        });
    }
};
