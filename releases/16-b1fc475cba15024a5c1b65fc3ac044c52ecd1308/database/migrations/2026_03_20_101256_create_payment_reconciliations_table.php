<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Reconciliation Period
            $table->date('reconciliation_date');
            $table->time('reconciliation_time')->default('02:00:00'); // 2 AM EAT
            
            // Totals
            $table->decimal('dashboard_total', 12, 2);
            $table->decimal('mpesa_total', 12, 2);
            $table->decimal('bank_total', 12, 2)->nullable();
            
            // Discrepancies
            $table->decimal('discrepancy_amount', 12, 2)->default(0);
            $table->decimal('discrepancy_percentage', 5, 2)->default(0);
            $table->enum('status', ['matched', 'discrepancy', 'pending_review'])->default('pending_review');
            
            // Breakdown
            $table->integer('total_transactions')->default(0);
            $table->integer('matched_transactions')->default(0);
            $table->integer('missing_in_dashboard')->default(0);
            $table->integer('missing_in_mpesa')->default(0);
            $table->integer('amount_mismatches')->default(0);
            
            // Details (store discrepancy details)
            $table->json('discrepancy_details')->nullable();
            
            // Resolution
            $table->text('notes')->nullable();
            $table->foreignId('resolved_by')->nullable(); // User ID
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'reconciliation_date']);
            $table->index('status');
            $table->unique(['tenant_id', 'reconciliation_date', 'reconciliation_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};