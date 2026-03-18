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
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('subscription_expires_at');
            $table->string('phone')->nullable()->after('status');
            $table->boolean('whatsapp_notification')->default(false)->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn(['status', 'phone', 'whatsapp_notification']);
        });
    }
};
