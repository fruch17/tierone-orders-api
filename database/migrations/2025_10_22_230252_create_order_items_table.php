<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create optimized order_items table with proper foreign keys and indexes
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index()->comment('Reference to parent order');
            $table->string('product_name')->comment('Name of the product');
            $table->integer('quantity')->comment('Quantity ordered');
            $table->decimal('unit_price', 10, 2)->comment('Price per unit');
            $table->decimal('subtotal', 10, 2)->comment('Total for this item (quantity * unit_price)');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            
            // Additional indexes for performance
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};