<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Customer Info
            $table->string('phone', 15); // e.g., "254712345678"
            $table->string('customer_name')->nullable();
            
            // Package
            $table->foreignId('package_id')->constrained()->nullOnDelete();
            $table->string('package_name')->nullable(); // Snapshot at time of payment
            
            // Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('KES');
            
            // M-Pesa Details (IDEMPOTENCY KEY)
            $table->string('mpesa_checkout_request_id')->unique(); // 🔑 IDEMPOTENCY KEY
            $table->string('mpesa_receipt_number')->nullable()->unique(); // e.g., "QKH123ABC"
            $table->string('mpesa_transaction_id')->nullable();
            $table->string('mpesa_phone')->nullable(); // From M-Pesa callback
            
            // Payment Status (STATE MACHINE)
            $table->enum('status', [
                'initiated',    // STK Push sent
                'pending',      // Waiting for callback
                'confirmed',    // Callback received, processing
                'completed',    // Session activated
                'failed',       // Payment failed
                'refunded',     // Refunded to customer
                'cancelled',    // Cancelled by user
            ])->default('initiated');
            
            // Callback Data (Store full M-Pesa response for audit)
            $table->json('callback_data')->nullable();
            $table->integer('callback_attempts')->default(0);
            
            // Timing
            $table->timestamp('initiated_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Session Link
            $table->foreignId('session_id')->nullable()->constrained()->nullOnDelete();
            
            // Reconciliation
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->text('reconciliation_notes')->nullable();
            
            // Metadata
            $table->string('payment_channel')->default('mpesa'); // mpesa, cash, voucher
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // ⚡ CRITICAL INDEXES FOR PERFORMANCE
            $table->index('mpesa_checkout_request_id'); // Idempotency lookup O(1)
            $table->index('mpesa_receipt_number'); // Reconciliation lookup
            $table->index(['tenant_id', 'status']); // Dashboard queries
            $table->index(['tenant_id', 'created_at']); // Reports
            $table->index('phone'); // Customer lookup
            $table->index('status'); // Pending payments query
            
            // Composite index for reconciliation
            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};