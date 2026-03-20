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
        Schema::table('employee_details', function (Blueprint $table) {
            $table->date('health_certificate_expiry')->nullable()->after('join_date');
            $table->string('emergency_contact')->nullable()->after('health_certificate_expiry');
            $table->text('uniform_details')->nullable()->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn(['health_certificate_expiry', 'emergency_contact', 'uniform_details']);
        });
    }
};
