<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change user_id to client_id in orders table for proper multi-tenancy
     * This allows both admin and staff to work with the same client's orders
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Simply rename the column - Laravel will handle constraints automatically
            $table->renameColumn('user_id', 'client_id');
        });
        
        // Add index after renaming
        Schema::table('orders', function (Blueprint $table) {
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     * Change client_id back to user_id in orders table
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the index
            $table->dropIndex(['client_id']);
            
            // Rename the column back
            $table->renameColumn('client_id', 'user_id');
            
            // Add back the foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Add back the index
            $table->index('user_id');
        });
    }
};