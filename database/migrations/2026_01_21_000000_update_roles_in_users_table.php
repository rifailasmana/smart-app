<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to conform to the new enum
        DB::table('users')->where('role', 'dapur')->update(['role' => 'kitchen']);

        // Now, alter the table
        $roles = ['admin', 'owner', 'hrd', 'manager', 'kasir', 'waiter', 'kitchen', 'inventory'];
        $roles_str = "'" . implode("', '", $roles) . "'";

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM($roles_str) NOT NULL DEFAULT 'kitchen'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $roles = ['admin', 'owner', 'kasir', 'dapur', 'waiter', 'kitchen'];
        $roles_str = "'" . implode("', '", $roles) . "'";
        
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM($roles_str) NOT NULL DEFAULT 'kitchen'");
        }
    }
};
