<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add role field to users table for multi-tenancy
     * Roles: 'admin' (can manage staff) and 'staff' (regular users)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff'])
                  ->default('staff')
                  ->after('company_name')
                  ->comment('User role: admin (can manage staff) or staff (regular user)');
        });
    }

    /**
     * Reverse the migrations.
     * Remove role field from users table
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};