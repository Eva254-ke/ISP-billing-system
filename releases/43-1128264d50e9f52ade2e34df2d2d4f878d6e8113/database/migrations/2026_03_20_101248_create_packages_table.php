<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Identity
            $table->string('name'); // e.g., "1 Hour Pass"
            $table->text('description')->nullable();
            $table->string('code')->unique(); // e.g., "1HR-001"
            
            // Pricing
            $table->decimal('price', 10, 2); // KES
            $table->string('currency')->default('KES');
            
            // Duration
            $table->integer('duration_value'); // e.g., 60
            $table->enum('duration_unit', ['minutes', 'hours', 'days', 'weeks', 'months']);
            
            // Bandwidth Limits (MikroTik Rate Limit)
            $table->integer('download_limit_mbps')->nullable(); // e.g., 5
            $table->integer('upload_limit_mbps')->nullable(); // e.g., 2
            $table->integer('data_limit_mb')->nullable(); // e.g., 1000
            
            // MikroTik Integration
            $table->string('mikrotik_profile_name')->nullable(); // e.g., "profile-1hour"
            $table->string('mikrotik_pool_name')->nullable(); // IP pool
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // Sales Tracking
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            
            // Display Order
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index('code');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};