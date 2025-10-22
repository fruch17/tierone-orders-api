<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix user_id index conflict for testing
     */
    public function up(): void
    {
        // Check if the index exists and drop it if it does
        if (Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Try to drop the index if it exists
                try {
                    $table->dropIndex(['user_id']);
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
                
                // Drop the column
                $table->dropColumn('user_id');
            });
        }
        
        // Recreate the column and index properly
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                  ->after('client_id')
                  ->comment('User who created the order (for audit trail)');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
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