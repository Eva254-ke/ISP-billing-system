<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_sessions')) {
            return;
        }

        Schema::table('user_sessions', function (Blueprint $table): void {
            if (!$this->indexExists('user_sessions', 'user_sessions_radius_identity_lookup_idx')) {
                $table->index(
                    ['tenant_id', 'username', 'status', 'expires_at'],
                    'user_sessions_radius_identity_lookup_idx'
                );
            }

            if (!$this->indexExists('user_sessions', 'user_sessions_radius_mac_lookup_idx')) {
                $table->index(
                    ['tenant_id', 'mac_address', 'status', 'expires_at'],
                    'user_sessions_radius_mac_lookup_idx'
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_sessions')) {
            return;
        }

        Schema::table('user_sessions', function (Blueprint $table): void {
            if ($this->indexExists('user_sessions', 'user_sessions_radius_identity_lookup_idx')) {
                $table->dropIndex('user_sessions_radius_identity_lookup_idx');
            }

            if ($this->indexExists('user_sessions', 'user_sessions_radius_mac_lookup_idx')) {
                $table->dropIndex('user_sessions_radius_mac_lookup_idx');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => (string) ($row->name ?? '') === $index);
        }

        return collect(DB::select('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$index]))
            ->isNotEmpty();
    }
};
