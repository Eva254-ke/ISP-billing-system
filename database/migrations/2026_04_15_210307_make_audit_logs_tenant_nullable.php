<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('actor_type')->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        DB::table('audit_logs_temp')->insertUsing(
            [
                'id',
                'tenant_id',
                'event',
                'entity_type',
                'entity_id',
                'actor_type',
                'actor_id',
                'actor_name',
                'old_values',
                'new_values',
                'metadata',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            ],
            DB::table('audit_logs')->select(
                'id',
                'tenant_id',
                'event',
                'entity_type',
                'entity_id',
                'actor_type',
                'actor_id',
                'actor_name',
                'old_values',
                'new_values',
                'metadata',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            )
        );

        Schema::drop('audit_logs');
        Schema::rename('audit_logs_temp', 'audit_logs');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['tenant_id', 'event']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // Rollback removes platform-level logs that do not belong to a tenant.
        DB::table('audit_logs')->whereNull('tenant_id')->delete();

        Schema::create('audit_logs_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('actor_type')->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        DB::table('audit_logs_temp')->insertUsing(
            [
                'id',
                'tenant_id',
                'event',
                'entity_type',
                'entity_id',
                'actor_type',
                'actor_id',
                'actor_name',
                'old_values',
                'new_values',
                'metadata',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            ],
            DB::table('audit_logs')->select(
                'id',
                'tenant_id',
                'event',
                'entity_type',
                'entity_id',
                'actor_type',
                'actor_id',
                'actor_name',
                'old_values',
                'new_values',
                'metadata',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            )
        );

        Schema::drop('audit_logs');
        Schema::rename('audit_logs_temp', 'audit_logs');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['tenant_id', 'event']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }
};
