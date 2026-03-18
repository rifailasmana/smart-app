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
            $table->boolean('google_sheets_enabled')->default(false);
            $table->string('google_sheets_spreadsheet_id')->nullable();
            $table->string('google_sheets_sheet_name')->nullable();
            $table->timestamp('google_sheets_last_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warungs', function (Blueprint $table) {
            $table->dropColumn([
                'google_sheets_enabled',
                'google_sheets_spreadsheet_id',
                'google_sheets_sheet_name',
                'google_sheets_last_synced_at',
            ]);
        });
    }
};
