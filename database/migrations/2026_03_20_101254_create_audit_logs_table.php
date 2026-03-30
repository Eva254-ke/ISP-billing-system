<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            // Action
            $table->string('event'); // e.g., "payment.completed", "session.terminated"
            $table->string('entity_type'); // e.g., "Payment", "Session"
            $table->unsignedBigInteger('entity_id');
            
            // Actor
            $table->string('actor_type')->default('system'); // user, system, api, webhook
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            
            // Changes
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            
            // Context
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'event']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};