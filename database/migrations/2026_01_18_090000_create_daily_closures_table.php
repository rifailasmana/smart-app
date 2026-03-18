<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
            $table->date('date');
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->decimal('average_transaction', 12, 2)->default(0);
            $table->foreignId('verified_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('closed_at');
            $table->timestamps();
            $table->unique(['warung_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closures');
    }
};

