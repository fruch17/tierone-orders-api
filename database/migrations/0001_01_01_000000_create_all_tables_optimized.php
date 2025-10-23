<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create all optimized tables with proper foreign keys and indexes
     */
    public function up(): void
    {
        // Create clients table first (no dependencies)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_email')->unique();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('company_email');
            $table->index('created_at');
        });

        // Create users table (depends on clients)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff');
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            
            // Foreign key constraint for client_id
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            
            // Additional indexes for performance
            $table->index(['client_id', 'role']);
        });

        // Create password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Create personal access tokens (Sanctum)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // Create orders table (depends on users and clients)
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
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Additional indexes for performance
            $table->index(['client_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['order_number']);
            $table->index(['total']);
        });

        // Create order items table (depends on orders)
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

        // Create cache tables
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Create job tables
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('clients');
    }
};
