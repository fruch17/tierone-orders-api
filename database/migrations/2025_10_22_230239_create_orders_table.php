<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create optimized orders table with proper foreign keys and indexes
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index()->comment('Client who owns the order (multi-tenancy)');
            $table->unsignedBigInteger('user_id')->index()->comment('User who created the order (audit trail)');
            $table->string('order_number')->unique()->comment('Unique order identifier');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('Subtotal before tax');
            $table->decimal('tax', 10, 2)->default(0)->comment('Tax amount');
            $table->decimal('total', 10, 2)->default(0)->comment('Total amount including tax');
            $table->text('notes')->nullable()->comment('Additional order notes');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Additional indexes for performance
            $table->index(['client_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};