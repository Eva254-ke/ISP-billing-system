<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('routers')) {
            return;
        }

        Schema::table('routers', function (Blueprint $table) {
            if (!Schema::hasColumn('routers', 'cpu_usage')) {
                $table->integer('cpu_usage')->nullable()->after('last_seen_at');
            }

            if (!Schema::hasColumn('routers', 'memory_usage')) {
                $table->integer('memory_usage')->nullable()->after('cpu_usage');
            }

            if (!Schema::hasColumn('routers', 'active_sessions')) {
                $table->integer('active_sessions')->default(0)->after('memory_usage');
            }

            if (!Schema::hasColumn('routers', 'uptime_seconds')) {
                $table->integer('uptime_seconds')->default(0)->after('active_sessions');
            }

            if (!Schema::hasColumn('routers', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('last_seen_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('routers')) {
            return;
        }

        Schema::table('routers', function (Blueprint $table) {
            foreach (['last_sync_at', 'uptime_seconds', 'active_sessions', 'memory_usage', 'cpu_usage'] as $column) {
                if (Schema::hasColumn('routers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
