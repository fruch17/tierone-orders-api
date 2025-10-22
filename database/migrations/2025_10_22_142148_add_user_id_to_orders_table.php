<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add user_id field to orders table for audit trail
     * client_id: for multi-tenancy (which client the order belongs to)
     * user_id: for audit trail (which user created the order)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                  ->nullable() // Allow null initially
                  ->after('client_id')
                  ->comment('User who created the order (for audit trail)');
            
            // Add index for performance
            $table->index('user_id');
        });
        
        // Update existing orders to have user_id = client_id (assuming they were created by the client)
        DB::statement('UPDATE orders SET user_id = client_id WHERE user_id IS NULL');
        
        // Now make user_id NOT NULL and add foreign key constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * Remove user_id field from orders table
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};