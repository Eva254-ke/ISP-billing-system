<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            
            // Voucher Code
            $table->string('code', 50)->unique(); // e.g., "CB-WIFI-A1B2C3"
            $table->string('prefix')->default('CB-WIFI-');
            
            // Status
            $table->enum('status', ['unused', 'used', 'expired', 'revoked'])->default('unused');
            
            // Usage
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_phone')->nullable(); // Customer phone
            $table->string('used_by_mac')->nullable(); // Customer MAC address
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            
            // Validity
            $table->timestamp('valid_from')->default(now());
            $table->timestamp('valid_until'); // Expiry date
            $table->integer('validity_hours')->default(24); // From first use
            
            // Batch Info (for bulk generation)
            $table->foreignId('batch_id')->nullable(); // For grouping generated vouchers
            $table->string('batch_name')->nullable(); // e.g., "March 2026 - 50 vouchers"
            
            // Printing
            $table->boolean('printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // Extra data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for fast lookup
            $table->index('code');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'status', 'valid_until']);
            $table->index('batch_id');
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};