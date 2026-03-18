<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->boolean('enable_system_clock')->default(true);
            $table->string('system_clock_format', 10)->default('24h');
        });
    }

    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn(['enable_system_clock', 'system_clock_format']);
        });
    }
};

