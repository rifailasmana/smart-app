<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_closures', function (Blueprint $table) {
            $table->foreignId('opened_by')->nullable()->after('date')->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable()->after('opened_by');
        });
    }

    public function down(): void
    {
        Schema::table('daily_closures', function (Blueprint $table) {
            $table->dropForeign(['opened_by']);
            $table->dropColumn(['opened_by', 'opened_at']);
        });
    }
};

