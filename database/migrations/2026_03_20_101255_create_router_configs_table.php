<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            
            // Config Type
            $table->string('config_type'); // e.g., "hotspot", "pppoe", "firewall"
            
            // Content
            $table->text('config_content'); // RouterOS commands
            $table->text('config_hash'); // For change detection
            
            // Deployment
            $table->enum('status', ['pending', 'deployed', 'failed'])->default('pending');
            $table->timestamp('deployed_at')->nullable();
            $table->text('deployment_error')->nullable();
            
            // Rollback
            $table->text('previous_config')->nullable();
            $table->boolean('can_rollback')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['router_id', 'status']);
            $table->index('config_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_configs');
    }
};