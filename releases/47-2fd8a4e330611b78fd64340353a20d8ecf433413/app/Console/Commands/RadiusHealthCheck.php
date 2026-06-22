<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RadiusHealthCheck extends Command
{
    protected $signature = 'radius:health-check {--strict : Treat warnings as failures}';

    protected $description = 'Validate FreeRADIUS production readiness (config, DB connectivity, and required SQL tables).';

    public function handle(): int
    {
        $errors = [];
        $warnings = [];

        $enabled = (bool) config('radius.enabled', false);
        if (!$enabled) {
            $errors[] = 'RADIUS is disabled. Set RADIUS_ENABLED=true.';
        }

        $sharedSecret = trim((string) config('radius.shared_secret', ''));
        if ($sharedSecret === '') {
            $errors[] = 'RADIUS_SHARED_SECRET is empty.';
        }

        $serverIp = trim((string) config('radius.server_ip', ''));
        if ($serverIp === '') {
            $errors[] = 'RADIUS_SERVER_IP is empty.';
        }

        $connection = (string) config('radius.db_connection', 'radius');
        $dbConfig = (array) config("database.connections.{$connection}", []);

        if (empty($dbConfig)) {
            $errors[] = "Database connection '{$connection}' is not defined.";
        }

        $host = (string) ($dbConfig['host'] ?? '');
        $database = (string) ($dbConfig['database'] ?? '');
        $username = (string) ($dbConfig['username'] ?? '');
        $sslCa = trim((string) env('RADIUS_DB_SSL_CA', ''));

        if ($database === '') {
            $errors[] = 'RADIUS_DB_DATABASE is empty.';
        }

        if ($username === '') {
            $errors[] = 'RADIUS_DB_USERNAME is empty.';
        }

        if ($host !== '' && !in_array($host, ['127.0.0.1', 'localhost'], true) && $sslCa === '') {
            $warnings[] = 'RADIUS_DB_SSL_CA is empty for a remote managed database host.';
        }

        if (strtolower($username) === 'doadmin') {
            $warnings[] = 'Using doadmin for runtime is broad privilege; prefer a dedicated radius user in production.';
        }

        $tables = (array) config('radius.tables', []);
        $requiredTables = [
            (string) ($tables['radcheck'] ?? 'radcheck'),
            (string) ($tables['radreply'] ?? 'radreply'),
            (string) ($tables['radacct'] ?? 'radacct'),
        ];

        if (!$errors) {
            try {
                DB::connection($connection)->select('select 1 as ok');
                $this->info('DB connectivity: OK');

                foreach ($requiredTables as $table) {
                    if ($table === '') {
                        $errors[] = 'One or more radius table names are empty in config.';
                        continue;
                    }

                    if (!Schema::connection($connection)->hasTable($table)) {
                        $errors[] = "Missing required table: {$table}";
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = 'DB connectivity failed: ' . $e->getMessage();
            }
        }

        if ($warnings) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line('- ' . $warning);
            }
        }

        if ($errors) {
            $this->error('Radius health check failed:');
            foreach ($errors as $error) {
                $this->line('- ' . $error);
            }
            return self::FAILURE;
        }

        if ($warnings && $this->option('strict')) {
            $this->error('Strict mode enabled: warnings are treated as failures.');
            return self::FAILURE;
        }

        $this->info('Radius health check passed.');
        return self::SUCCESS;
    }
}
