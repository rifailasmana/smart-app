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
            $table->boolean('require_owner_auth_for_discount')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn('require_owner_auth_for_discount');
        });
    }
};
