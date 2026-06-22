<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Identity
            $table->string('name'); // e.g., "Main Hotspot"
            $table->string('model'); // e.g., "RB750Gr3", "hAP lite"
            $table->string('serial_number')->nullable();
            
            // Connection
            $table->ipAddress('ip_address');
            $table->integer('api_port')->default(8728);
            $table->string('api_username')->default('admin');
            $table->string('api_password'); // Encrypted
            $table->boolean('api_ssl')->default(false);
            
            // Location
            $table->string('location')->nullable(); // e.g., "Nairobi Office"
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            
            // Health Metrics
            $table->integer('cpu_usage')->nullable(); // Percentage
            $table->integer('memory_usage')->nullable(); // Percentage
            $table->integer('active_sessions')->default(0);
            $table->integer('uptime_seconds')->default(0);
            
            // Configuration
            $table->integer('accounting_interval')->default(60); // Seconds
            $table->boolean('ntp_enabled')->default(true);
            $table->string('ntp_server')->default('pool.ntp.org');
            
            // Metadata
            $table->json('config_backup')->nullable(); // Last config backup
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for fast lookup
            $table->index(['tenant_id', 'status']);
            $table->index('ip_address');
            $table->index('last_seen_at');
            
            // Composite index for tenant-scoped queries
            $table->index(['tenant_id', 'status', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};