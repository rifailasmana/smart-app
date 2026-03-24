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
		Schema::create('order_item_voids', function (Blueprint $table) {
			$table->id();
			$table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
			$table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
			$table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
			$table->integer('qty')->default(1);
			$table->integer('prev_qty')->nullable();
			$table->string('reason')->nullable();
			$table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
			$table->string('voided_by_role')->nullable();
			$table->string('manager_pin_used')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('order_item_voids');
	}
};
