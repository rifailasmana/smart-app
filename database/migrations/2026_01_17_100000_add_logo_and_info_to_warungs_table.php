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
            $table->string('logo')->nullable()->after('description');
            $table->text('address')->nullable()->after('logo');
            $table->string('opening_hours')->nullable()->after('address');
            $table->string('contact_email')->nullable()->after('opening_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn(['logo', 'address', 'opening_hours', 'contact_email']);
        });
    }
};
