<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE daily_closures MODIFY verified_by BIGINT UNSIGNED NULL;');
        DB::statement('ALTER TABLE daily_closures MODIFY closed_at TIMESTAMP NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE daily_closures MODIFY verified_by BIGINT UNSIGNED NOT NULL;');
        DB::statement('ALTER TABLE daily_closures MODIFY closed_at TIMESTAMP NOT NULL;');
    }
};

