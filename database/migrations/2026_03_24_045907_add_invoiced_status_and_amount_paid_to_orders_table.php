<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add amount_paid if it doesn't exist
            if (!Schema::hasColumn('orders', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->nullable()->after('total');
            }
        });

        // Update status enum to include 'invoiced'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','verified','preparing','ready','served','paid','cancelled','invoiced') NOT NULL DEFAULT 'pending';");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','verified','preparing','ready','served','paid','cancelled') NOT NULL DEFAULT 'pending';");
        }
    }
};
