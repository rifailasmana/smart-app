<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_discount_percent')->default(50)->after('whatsapp_notification');
            $table->unsignedInteger('max_discount_amount')->nullable()->after('max_discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn(['max_discount_percent', 'max_discount_amount']);
        });
    }
};

