<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHealthCheck extends Command
{
    protected $signature = 'db:health-check
                            {--connection=* : Only check the named connection(s)}
                            {--strict : Treat warnings as failures}';

    protected $description = 'Check database connectivity and critical schema health for the app and RADIUS databases.';

    public function handle(): int
    {
        $warnings = [];
        $errors = [];
        $rows = [];

        foreach ($this->resolveConnectionsToCheck($warnings) as $connection) {
            $result = $this->checkConnection($connection);
            $rows[] = [
                $result['label'],
                $result['connection'],
                $result['status'],
                $result['latency_ms'],
                $result['details'],
            ];

            foreach ($result['warnings'] as $warning) {
                $warnings[] = "[{$result['label']}] {$warning}";
            }

            foreach ($result['errors'] as $error) {
                $errors[] = "[{$result['label']}] {$error}";
            }
        }

        if ($rows !== []) {
            $this->table(['Target', 'Connection', 'Status', 'Latency', 'Details'], $rows);
        }

        if ($warnings !== []) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line('- ' . $warning);
            }
        }

        if ($errors !== []) {
            $this->error('Database health check failed:');
            foreach ($errors as $error) {
                $this->line('- ' . $error);
            }

            return self::FAILURE;
        }

        if ($warnings !== [] && $this->option('strict')) {
            $this->error('Strict mode enabled: warnings are treated as failures.');

            return self::FAILURE;
        }

        $this->info('Database health check passed.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{key:string,label:string,required:bool}>
     */
    private function resolveConnectionsToCheck(array &$warnings): array
    {
        $selected = array_values(array_filter(
            array_map(static fn ($value) => trim((string) $value), (array) $this->option('connection')),
            static fn ($value) => $value !== ''
        ));

        if ($selected !== []) {
            return array_map(function (string $connection): array {
                return [
                    'key' => $connection,
                    'label' => strtoupper($connection),
                    'required' => true,
                ];
            }, array_values(array_unique($selected)));
        }

        $targets = [[
            'key' => (string) config('database.default', 'mysql'),
            'label' => 'APP',
            'required' => true,
        ]];

        $radiusConnection = (string) config('radius.db_connection', 'radius');
        if ((bool) config('radius.enabled', false)) {
            $targets[] = [
                'key' => $radiusConnection,
                'label' => 'RADIUS',
                'required' => true,
            ];
        } elseif (config("database.connections.{$radiusConnection}") !== null) {
            $warnings[] = "RADIUS is disabled, so the '{$radiusConnection}' connection was skipped.";
        }

        return $targets;
    }

    /**
     * @param array{key:string,label:string,required:bool} $target
     * @return array{
     *   label:string,
     *   connection:string,
     *   status:string,
     *   latency_ms:string,
     *   details:string,
     *   warnings:array<int,string>,
     *   errors:array<int,string>
     * }
     */
    private function checkConnection(array $target): array
    {
        $connection = $target['key'];
        $label = $target['label'];
        $warnings = [];
        $errors = [];
        $latencyMs = 'n/a';
        $details = '';

        $dbConfig = config("database.connections.{$connection}");
        if (!is_array($dbConfig)) {
            return [
                'label' => $label,
                'connection' => $connection,
                'status' => 'FAIL',
                'latency_ms' => $latencyMs,
                'details' => 'Connection is not defined',
                'warnings' => [],
                'errors' => ["Database connection '{$connection}' is not defined."],
            ];
        }

        $driver = (string) ($dbConfig['driver'] ?? 'unknown');
        $database = (string) ($dbConfig['database'] ?? '');
        $host = (string) ($dbConfig['host'] ?? '');

        try {
            $startedAt = microtime(true);
            DB::connection($connection)->select('select 1 as ok');
            $latencyMs = number_format((microtime(true) - $startedAt) * 1000, 2) . 'ms';
        } catch (\Throwable $e) {
            return [
                'label' => $label,
                'connection' => $connection,
                'status' => 'FAIL',
                'latency_ms' => $latencyMs,
                'details' => 'Connectivity failed',
                'warnings' => [],
                'errors' => ['Connectivity failed: ' . $e->getMessage()],
            ];
        }

        if ($database === '' && $driver !== 'sqlite') {
            $errors[] = 'Database name is empty.';
        }

        if ($host === '' && !in_array($driver, ['sqlite'], true)) {
            $warnings[] = 'Database host is empty.';
        }

        if ($label === 'APP') {
            $this->checkAppSchema($connection, $warnings, $errors);
        }

        if ($label === 'RADIUS') {
            $this->checkRadiusSchema($connection, $warnings, $errors);
        }

        $status = $errors === [] ? ($warnings === [] ? 'OK' : 'WARN') : 'FAIL';
        $details = trim(implode(' | ', array_filter([
            $driver !== '' ? "driver={$driver}" : null,
            $host !== '' ? "host={$host}" : null,
            $database !== '' ? "db={$database}" : null,
        ])));

        return [
            'label' => $label,
            'connection' => $connection,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'details' => $details !== '' ? $details : 'Connected',
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    private function checkAppSchema(string $connection, array &$warnings, array &$errors): void
    {
        $requiredTables = ['migrations', 'payments', 'user_sessions', 'tenants', 'packages'];
        foreach ($requiredTables as $table) {
            if (!Schema::connection($connection)->hasTable($table)) {
                $errors[] = "Missing required app table: {$table}";
            }
        }

        if (!Schema::connection($connection)->hasTable('payments')) {
            return;
        }

        $requiredPaymentColumns = [
            'status',
            'type',
            'payment_channel',
            'mpesa_checkout_request_id',
            'metadata',
            'callback_data',
        ];

        foreach ($requiredPaymentColumns as $column) {
            if (!Schema::connection($connection)->hasColumn('payments', $column)) {
                $errors[] = "payments.{$column} column is missing.";
            }
        }

        if (Schema::connection($connection)->hasColumn('payments', 'status')) {
            try {
                DB::connection($connection)->table('payments')->limit(1)->get();
            } catch (\Throwable $e) {
                $warnings[] = 'payments table is reachable but a simple read failed: ' . $e->getMessage();
            }
        }
    }

    private function checkRadiusSchema(string $connection, array &$warnings, array &$errors): void
    {
        $tables = (array) config('radius.tables', []);
        $requiredTables = [
            (string) ($tables['radcheck'] ?? 'radcheck'),
            (string) ($tables['radreply'] ?? 'radreply'),
            (string) ($tables['radacct'] ?? 'radacct'),
            (string) ($tables['radpostauth'] ?? 'radpostauth'),
            (string) ($tables['nasreload'] ?? 'nasreload'),
        ];

        foreach ($requiredTables as $table) {
            if ($table === '') {
                $errors[] = 'One or more RADIUS table names are empty in config.';
                continue;
            }

            if (!Schema::connection($connection)->hasTable($table)) {
                $errors[] = "Missing required RADIUS table: {$table}";
            }
        }

        $sslCa = trim((string) env('RADIUS_DB_SSL_CA', ''));
        $host = trim((string) config("database.connections.{$connection}.host", ''));
        if ($host !== '' && !in_array($host, ['127.0.0.1', 'localhost'], true) && $sslCa === '') {
            $warnings[] = 'RADIUS_DB_SSL_CA is empty for a remote RADIUS database host.';
        }
    }
}
