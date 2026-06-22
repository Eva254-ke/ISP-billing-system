<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasTable('user_sessions') || !Schema::hasColumn('payments', 'session_id')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['session_id']);
            });
        } catch (\Throwable) {
            // Ignore missing or differently named legacy constraints.
        }

        DB::table('payments')
            ->whereNotNull('session_id')
            ->whereNotIn('session_id', DB::table('user_sessions')->select('id'))
            ->update(['session_id' => null]);

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('session_id')
                ->references('id')
                ->on('user_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasTable('sessions') || !Schema::hasColumn('payments', 'session_id')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['session_id']);
            });
        } catch (\Throwable) {
            // Ignore missing constraints during rollback.
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('session_id')
                ->references('id')
                ->on('sessions')
                ->nullOnDelete();
        });
    }
};
