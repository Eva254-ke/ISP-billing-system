<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Multi-tenant support
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            
            // Role & Permissions
            $table->enum('role', ['super_admin', 'tenant_admin', 'operator', 'viewer'])->default('viewer');
            $table->json('permissions')->nullable();
            
            // Profile
            $table->string('phone')->nullable();
            $table->string('timezone')->default('Africa/Nairobi');
            
            // Security & Activity
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            
            // Indexes for performance
            $table->index(['tenant_id', 'role']);
            $table->index('email');
            $table->index('is_active');
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropIndex('email');
            $table->dropIndex('is_active');
            $table->dropIndex('last_login_at');
            
            // Then drop columns
            $table->dropForeign(['tenant_id']);
            $table->dropColumn([
                'tenant_id',
                'role',
                'permissions',
                'phone',
                'timezone',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
            ]);
        });
    }
};