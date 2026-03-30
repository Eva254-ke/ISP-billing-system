<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->nullOnDelete();
            
            // User Identity
            $table->string('username')->unique();
            $table->string('phone')->nullable();
            $table->string('mac_address')->nullable();
            $table->ipAddress('ip_address')->nullable();
            
            // Session State
            $table->enum('status', [
                'active',
                'idle',
                'expired',
                'terminated',
                'suspended'
            ])->default('active');
            
            // Timing (CRITICAL FOR EARLY DISCONNECT PREVENTION)
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            
            // Grace Period (PREVENTS PREMATURE DISCONNECTS)
            $table->boolean('grace_period_active')->default(false);
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->integer('grace_period_seconds')->default(300);
            
            // Data Usage (from RADIUS accounting)
            $table->unsignedBigInteger('bytes_in')->default(0);
            $table->unsignedBigInteger('bytes_out')->default(0);
            $table->unsignedBigInteger('bytes_total')->default(0);
            $table->integer('data_limit_mb')->nullable();
            
            // MikroTik Session Info
            $table->string('mikrotik_session_id')->nullable();
            $table->string('mikrotik_user_profile')->nullable();
            $table->integer('mikrotik_uptime_seconds')->default(0);
            
            // Payment Link
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            
            // Sync Status
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('sync_failed')->default(false);
            $table->integer('sync_retry_count')->default(0);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // ⚡ CRITICAL INDEXES
            $table->index('username');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'expires_at']);
            $table->index('mac_address');
            $table->index('phone');
            $table->index('router_id');
            $table->index('expires_at');
            $table->index(['tenant_id', 'router_id', 'status', 'expires_at']);
            $table->index(['grace_period_active', 'grace_period_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};