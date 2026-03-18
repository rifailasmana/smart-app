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
        Schema::table('warungs', function (Blueprint $table) {
            $table->enum('subscription_tier', ['starter', 'professional', 'enterprise'])->default('starter');
            $table->decimal('monthly_price', 10, 2)->default(150000);
            $table->timestamp('subscription_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn(['subscription_tier', 'monthly_price', 'subscription_expires_at']);
        });
    }
};
