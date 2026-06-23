<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('packages', 'duration_minutes')) {
            return;
        }

        DB::statement("
            ALTER TABLE `packages`
            ADD COLUMN `duration_minutes` INT GENERATED ALWAYS AS (
                CASE `duration_unit`
                    WHEN 'minutes' THEN `duration_value`
                    WHEN 'hours'   THEN `duration_value` * 60
                    WHEN 'days'    THEN `duration_value` * 1440
                    WHEN 'weeks'   THEN `duration_value` * 10080
                    WHEN 'months'  THEN `duration_value` * 43200
                    ELSE `duration_value`
                END
            ) VIRTUAL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('packages', 'duration_minutes')) {
            return;
        }

        DB::statement("ALTER TABLE `packages` DROP COLUMN `duration_minutes`");
    }
};