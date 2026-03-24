<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');

            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->string('table_number', 64)->nullable();

            $table->string('customer_name')->nullable();
            $table->string('order_code', 64)->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('admin_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->enum('status', ['outstanding', 'paid'])->default('outstanding');

            $table->timestamp('revenue_recognized_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('cashier_name')->nullable();

            $table->json('items_snapshot')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['warung_id', 'status']);
            $table->index(['warung_id', 'revenue_recognized_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivables');
    }
};

