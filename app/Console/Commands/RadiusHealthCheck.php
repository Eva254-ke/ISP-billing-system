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
        $appEnv = strtolower((string) config('app.env', env('APP_ENV', 'production')));
        $isProduction = in_array($appEnv, ['production', 'prod'], true);

        $enabled = (bool) config('radius.enabled', false);
        if (!$enabled) {
            $errors[] = 'RADIUS is disabled. Set RADIUS_ENABLED=true.';
        }

        $sharedSecret = trim((string) config('radius.shared_secret', ''));
        if ($sharedSecret === '') {
            $errors[] = 'RADIUS_SHARED_SECRET is empty.';
        } elseif (in_array(strtolower($sharedSecret), ['your-radius-secret', 'changeme', 'change-me', 'radius-secret'], true)) {
            $errors[] = 'RADIUS_SHARED_SECRET is still a placeholder value.';
        }

        $serverIp = trim((string) config('radius.server_ip', ''));
        if ($serverIp === '') {
            $errors[] = 'RADIUS_SERVER_IP is empty.';
        } elseif (in_array(strtolower($serverIp), ['127.0.0.1', 'localhost', '::1'], true)) {
            if ($isProduction) {
                $errors[] = 'RADIUS_SERVER_IP is loopback in production. Use the reachable FreeRADIUS server IP.';
            } else {
                $warnings[] = 'RADIUS_SERVER_IP is loopback; this is only valid when router and RADIUS are on the same host.';
            }
        }

        $authPort = (int) config('radius.auth_port', 1812);
        $acctPort = (int) config('radius.acct_port', 1813);
        if ($authPort < 1 || $authPort > 65535) {
            $errors[] = 'RADIUS_AUTH_PORT must be between 1 and 65535.';
        }
        if ($acctPort < 1 || $acctPort > 65535) {
            $errors[] = 'RADIUS_ACCT_PORT must be between 1 and 65535.';
        }
        if ($authPort === $acctPort) {
            $warnings[] = 'RADIUS auth and accounting ports are identical; verify this is intentional.';
        }

        $this->checkLocalRadiusUdpPorts($serverIp, $authPort, $acctPort, $warnings, $errors);

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
            if ($isProduction) {
                $errors[] = 'RADIUS_DB_SSL_CA is empty for a remote database host in production.';
            } else {
                $warnings[] = 'RADIUS_DB_SSL_CA is empty for a remote managed database host.';
            }
        }

        if ($sslCa !== '' && !is_file($sslCa)) {
            $warnings[] = "RADIUS_DB_SSL_CA file not found at '{$sslCa}'.";
        }

        if (strtolower($username) === 'doadmin') {
            $warnings[] = 'Using doadmin for runtime is broad privilege; prefer a dedicated radius user in production.';
        }

        $tables = (array) config('radius.tables', []);
        $requiredTables = [
            (string) ($tables['radcheck'] ?? 'radcheck'),
            (string) ($tables['radreply'] ?? 'radreply'),
            (string) ($tables['radacct'] ?? 'radacct'),
            (string) ($tables['radpostauth'] ?? 'radpostauth'),
            (string) ($tables['nasreload'] ?? 'nasreload'),
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

    private function checkLocalRadiusUdpPorts(string $serverIp, int $authPort, int $acctPort, array &$warnings, array &$errors): void
    {
        foreach ([
            'auth' => $authPort,
            'accounting' => $acctPort,
        ] as $label => $port) {
            $probe = $this->probeLocalUdpPort($serverIp, $port);

            if ($probe['status'] === 'free') {
                $errors[] = "No local process appears to be bound to the RADIUS {$label} UDP port {$port} on {$probe['host']}. FreeRADIUS may be down or listening on the wrong interface.";
                continue;
            }

            if ($probe['status'] === 'warning') {
                $warnings[] = $probe['message'];
            }
        }
    }

    /**
     * Attempt to determine whether a local UDP port is already in use.
     *
     * @return array{status:string,host:string,message:string}
     */
    private function probeLocalUdpPort(string $host, int $port): array
    {
        $resolvedHost = $this->resolveProbeHost($host);
        if ($resolvedHost === null || $port < 1 || $port > 65535) {
            return [
                'status' => 'skip',
                'host' => $host,
                'message' => '',
            ];
        }

        if (!function_exists('socket_create') || !function_exists('socket_bind')) {
            return [
                'status' => 'warning',
                'host' => $resolvedHost,
                'message' => "PHP sockets extension is unavailable, so the local UDP probe for {$resolvedHost}:{$port} was skipped.",
            ];
        }

        $family = str_contains($resolvedHost, ':') ? AF_INET6 : AF_INET;
        $socket = @socket_create($family, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return [
                'status' => 'warning',
                'host' => $resolvedHost,
                'message' => "Unable to create a UDP socket to probe {$resolvedHost}:{$port}.",
            ];
        }

        $bound = @socket_bind($socket, $resolvedHost, $port);

        if ($bound) {
            socket_close($socket);

            return [
                'status' => 'free',
                'host' => $resolvedHost,
                'message' => '',
            ];
        }

        $errno = socket_last_error($socket);
        $error = strtolower(socket_strerror($errno));
        socket_close($socket);

        if ($this->isAddressInUseError($errno, $error)) {
            return [
                'status' => 'in_use',
                'host' => $resolvedHost,
                'message' => '',
            ];
        }

        if ($this->isNonLocalAddressError($errno, $error)) {
            return [
                'status' => 'skip',
                'host' => $resolvedHost,
                'message' => '',
            ];
        }

        return [
            'status' => 'warning',
            'host' => $resolvedHost,
            'message' => "Unable to probe the local UDP port {$resolvedHost}:{$port}: {$error}.",
        ];
    }

    private function resolveProbeHost(string $host): ?string
    {
        $candidate = trim($host);
        if ($candidate === '') {
            return null;
        }

        if (strtolower($candidate) === 'localhost') {
            return '127.0.0.1';
        }

        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }

        $resolved = gethostbyname($candidate);
        if ($resolved === $candidate || !filter_var($resolved, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $resolved;
    }

    private function isAddressInUseError(int $errno, string $error): bool
    {
        return in_array($errno, [48, 98, 10048], true) || str_contains($error, 'in use');
    }

    private function isNonLocalAddressError(int $errno, string $error): bool
    {
        return in_array($errno, [49, 99, 10049], true)
            || str_contains($error, 'requested address')
            || str_contains($error, 'cannot assign')
            || str_contains($error, 'not available');
    }
}
