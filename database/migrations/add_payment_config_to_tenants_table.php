<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Payment method: paybill, till, personal, bank_eazzy
            $table->enum('payment_method', ['paybill', 'till', 'personal', 'bank_eazzy'])
                ->default('paybill');
            
            // Shortcode (Paybill) or Till number
            $table->string('payment_shortcode')->nullable(); // e.g., "247247" or "123456"
            
            // Till number (if method = 'till')
            $table->string('till_number')->nullable(); // e.g., "123456"
            
            // Account name for Paybill transactions
            $table->string('payment_account_name')->nullable(); // e.g., "Nyamira WiFi"
            
            // Bank account (for Equity EazzyPay)
            $table->string('bank_account')->nullable(); // e.g., "0123456789"
            $table->string('bank_code')->nullable(); // e.g., "EQTY" for Equity
            
            // Personal M-Pesa number (if method = 'personal')
            $table->string('personal_phone')->nullable(); // e.g., "254712345678"
            
            // Commission settings
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('commission_rate', 5, 2)->default(5.00); // 5% or KES amount
            $table->decimal('minimum_commission', 10, 2)->default(10.00); // Min KES 10
            
            // Commission billing
            $table->enum('commission_frequency', ['monthly', 'weekly', 'per_transaction'])
                ->default('monthly');
            $table->date('next_commission_date')->nullable();
            
            // Callback URL override (if tenant has custom endpoint)
            $table->string('custom_callback_url')->nullable();
            
            // Indexes
            $table->index('payment_method');
            $table->index('payment_shortcode');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_shortcode',
                'till_number',
                'payment_account_name',
                'bank_account',
                'bank_code',
                'personal_phone',
                'commission_type',
                'commission_rate',
                'minimum_commission',
                'commission_frequency',
                'next_commission_date',
                'custom_callback_url',
            ]);
        });
    }
};