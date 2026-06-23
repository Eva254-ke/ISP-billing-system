<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $schema = $this->resolveRadiusSchema();
        if ($schema === null) {
            return;
        }

        $radacct = (string) (config('radius.tables.radacct', 'radacct') ?: 'radacct');
        if ($radacct === '' || !$schema->hasTable($radacct)) {
            return;
        }

        $missingColumns = [];

        foreach ([
            'realm',
            'nasportid',
            'nasporttype',
            'acctauthentic',
            'connectinfo_start',
            'connectinfo_stop',
            'servicetype',
            'framedprotocol',
            'framedipv6address',
            'framedipv6prefix',
            'framedinterfaceid',
            'delegatedipv6prefix',
        ] as $column) {
            if (!$schema->hasColumn($radacct, $column)) {
                $missingColumns[$column] = true;
            }
        }

        if ($missingColumns === []) {
            return;
        }

        $schema->table($radacct, function (Blueprint $table) use ($missingColumns): void {
            if (isset($missingColumns['realm'])) {
                $table->string('realm', 64)->default('');
            }

            if (isset($missingColumns['nasportid'])) {
                $table->string('nasportid', 15)->nullable();
            }

            if (isset($missingColumns['nasporttype'])) {
                $table->string('nasporttype', 32)->nullable();
            }

            if (isset($missingColumns['acctauthentic'])) {
                $table->string('acctauthentic', 32)->nullable();
            }

            if (isset($missingColumns['connectinfo_start'])) {
                $table->string('connectinfo_start', 50)->nullable();
            }

            if (isset($missingColumns['connectinfo_stop'])) {
                $table->string('connectinfo_stop', 50)->nullable();
            }

            if (isset($missingColumns['servicetype'])) {
                $table->string('servicetype', 32)->nullable();
            }

            if (isset($missingColumns['framedprotocol'])) {
                $table->string('framedprotocol', 32)->nullable();
            }

            if (isset($missingColumns['framedipv6address'])) {
                $table->string('framedipv6address', 45)->nullable();
            }

            if (isset($missingColumns['framedipv6prefix'])) {
                $table->string('framedipv6prefix', 45)->nullable();
            }

            if (isset($missingColumns['framedinterfaceid'])) {
                $table->string('framedinterfaceid', 44)->nullable();
            }

            if (isset($missingColumns['delegatedipv6prefix'])) {
                $table->string('delegatedipv6prefix', 45)->nullable();
            }
        });
    }

    public function down(): void
    {
        // This is a forward-only compatibility migration for an existing radius schema.
    }

    private function resolveRadiusSchema(): ?\Illuminate\Database\Schema\Builder
    {
        $connection = (string) config('radius.db_connection', 'radius');

        if (!is_array(config("database.connections.{$connection}"))) {
            return null;
        }

        try {
            $schema = Schema::connection($connection);
            $schema->hasTable('migrations');

            return $schema;
        } catch (\Throwable) {
            return null;
        }
    }
};
