<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Nyamira WiFi"
            $table->string('subdomain')->unique(); // e.g., "nyamira"
            $table->string('domain')->nullable(); // e.g., "nyamirawifi.co.ke"
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('currency')->default('KES');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            
            // Billing
            $table->enum('plan', ['starter', 'growth', 'pro', 'enterprise'])->default('starter');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->date('billing_cycle_start')->default(now());
            $table->date('next_billing_date');
            
            // Limits
            $table->integer('max_routers')->default(1);
            $table->integer('max_users')->default(100);
            
            // Metadata
            $table->json('settings')->nullable(); // Tenant-specific settings
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for fast lookup
            $table->index('subdomain');
            $table->index('status');
            $table->index('next_billing_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};