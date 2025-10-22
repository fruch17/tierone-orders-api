<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add client_id field to users table for multi-tenancy
     * Admin users have client_id = 0 (they are their own client)
     * Staff users have client_id = admin_id (they belong to an admin's client)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')
                  ->default(0)
                  ->after('role')
                  ->comment('Client ID for multi-tenancy: 0 for admin (own client), admin_id for staff');
        });
    }

    /**
     * Reverse the migrations.
     * Remove client_id field from users table
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
};