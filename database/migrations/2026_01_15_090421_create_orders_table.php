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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
            $table->foreignId('table_id')->constrained('restaurant_tables')->onDelete('cascade');
            $table->string('code')->unique();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'paid'])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('admin_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_method', ['kasir', 'qris', 'gateway'])->default('kasir');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
