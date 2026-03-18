<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('diskon_manual', 10, 2)->default(0)->after('admin_fee');
            $table->string('alasan_diskon')->nullable()->after('diskon_manual');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['diskon_manual', 'alasan_diskon']);
        });
    }
};

