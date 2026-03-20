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
        // Add category to ingredients
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        // Add supplier_id to stock_logs for tracking where the stock came from
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('user_id')->constrained('suppliers')->onDelete('set null');
        });

        // Create Restock Requests (for Kitchen / Manager to Inventory)
        Schema::create('restock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Requester
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'done'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock_requests');
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
