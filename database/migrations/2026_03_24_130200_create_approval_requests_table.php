<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('approval_requests', function (Blueprint $table) {
			$table->id();
			$table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
			$table->string('type'); // e.g. void, refund, discount
			$table->json('payload')->nullable(); // arbitrary data (order/item info)
			$table->string('status')->default('pending'); // pending, approved, rejected
			$table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
			$table->string('reason')->nullable();
			$table->text('notes')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('approval_requests');
	}
};
