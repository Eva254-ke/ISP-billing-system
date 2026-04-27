<?php

namespace App\Services\MikroTik;

use App\Models\Router;
use App\Models\UserSession;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Exceptions\ClientException;
use RouterOS\Exceptions\QueryException;
use RouterOS\Exceptions\ConfigException;

class MikroTikService
{
    /**
     * Connection timeout (seconds)
     */
    private const CONNECTION_TIMEOUT = 10;
    
    /**
     * Command timeout (seconds)
     */
    private const COMMAND_TIMEOUT = 30;
    
    /**
     * Retry attempts for failed commands
     */
    private const MAX_RETRIES = 3;
    
    /**
     * Cache TTL for router connections (seconds)
     */
    private const CONNECTION_CACHE_TTL = 300; // 5 minutes

    /**
     * Last client initialization error captured during createClient().
     */
    private ?string $lastClientInitError = null;

    /**
     * Create user session on MikroTik (Hotspot login)
     */
    public function createHotspotSession(
        Router $router,
        string $username,
        string $password,
        int $durationMinutes,
        ?string $macAddress = null,
        ?string $ipAddress = null
    ): array {
        $result = $this->withRetry(function () use ($router, $username, $password, $durationMinutes, $macAddress, $ipAddress) {
            $client = $this->getRequiredClient($router);
            
            // Calculate expiry time
            $expiresAt = now()->addMinutes($durationMinutes);
            
            // Login user to hotspot.
            $loginResponse = $this->loginHotspotUser(
                client: $client,
                username: $username,
                password: $password,
                macAddress: $macAddress,
                ipAddress: $ipAddress
            );

            $normalizedMac = $this->normalizeMacAddressForQuery($macAddress);
            $normalizedIp = is_string($ipAddress) ? trim($ipAddress) : null;

            $activeSession = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $activeSession = $this->findActiveHotspotSession(
                    client: $client,
                    username: $username,
                    macAddress: $normalizedMac,
                    ipAddress: $normalizedIp
                );

                if ($activeSession !== null) {
                    break;
                }

                usleep(300000); // Router may take a short moment to materialize active sessions.
            }

            if ($activeSession === null) {
                $missingClientContext = ($normalizedMac === null || $normalizedMac === '')
                    && ($normalizedIp === null || $normalizedIp === '');

                $error = $missingClientContext
                    ? 'Hotspot login command sent, but no active session was created. Missing client MAC/IP context.'
                    : 'Hotspot login command sent, but no active session was detected on the router.';

                Log::channel('mikrotik')->warning('Hotspot login did not create an active session', [
                    'router' => $router->name,
                    'username' => $username,
                    'mac_address' => $normalizedMac,
                    'ip_address' => $normalizedIp,
                    'missing_client_context' => $missingClientContext,
                    'login_response' => $loginResponse,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                    'queued' => false,
                    'missing_client_context' => $missingClientContext,
                ];
            }
            
            // Set session timeout (MikroTik uses seconds)
            $timeoutSeconds = $durationMinutes * 60;
            try {
                $this->setSessionTimeout($client, $username, $timeoutSeconds);
            } catch (\Throwable $timeoutError) {
                // Radius users may not exist in local hotspot user table. Avoid failing activation for that.
                Log::channel('mikrotik')->warning('Unable to set local hotspot user timeout; continuing activation', [
                    'router' => $router->name,
                    'username' => $username,
                    'error' => $timeoutError->getMessage(),
                ]);
            }
            
            Log::channel('mikrotik')->info('Hotspot session created', [
                'router' => $router->name,
                'username' => $username,
                'expires_at' => $expiresAt->toIso8601String(),
                'duration_minutes' => $durationMinutes,
                'mac_address' => $normalizedMac,
                'ip_address' => $normalizedIp,
                'active_host_mac' => $activeSession['mac-address'] ?? null,
                'active_host_ip' => $activeSession['address'] ?? null,
            ]);
            
            return [
                'success' => true,
                'username' => $username,
                'expires_at' => $expiresAt,
                'mikrotik_response' => $loginResponse,
                'active_session' => $activeSession,
            ];
            
        }, $router, 'createHotspotSession');

        // Ensure we always return an array (fix return type contract)
        if (is_array($result) && isset($result['success'])) {
            return $result;
        }

        // Fallback response when retries exhausted
        try {
            $this->pingRouter($router);
            $router = $router->fresh();
        } catch (\Throwable $refreshError) {
            Log::channel('mikrotik')->warning('Router connectivity refresh failed after hotspot session retries were exhausted', [
                'router' => $router->name,
                'router_id' => $router->id,
                'username' => $username,
                'error' => $refreshError->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'error' => 'Unable to create hotspot session after retries.',
            'queued' => (string) ($router->status ?? '') === Router::STATUS_OFFLINE,
        ];
    }

    /**
     * Trigger hotspot login for a user. New RouterOS versions expose this on /ip/hotspot/active/login.
     */
    private function loginHotspotUser(
        Client $client,
        string $username,
        string $password,
        ?string $macAddress = null,
        ?string $ipAddress = null
    ): array {
        $query = (new Query('/ip/hotspot/active/login'))
            ->equal('user', $username)
            ->equal('password', $password);

        if ($macAddress !== null && $macAddress !== '') {
            $query->equal('mac-address', $macAddress);
        }

        if ($ipAddress !== null && $ipAddress !== '') {
            $query->equal('ip', $ipAddress);
        }

        try {
            return $client->query($query)->read() ?? [];
        } catch (QueryException $error) {
            // Backward-compatible fallback for older setups.
            $legacyQuery = (new Query('/ip/hotspot/login'))
                ->equal('name', $username)
                ->equal('password', $password);

            if ($macAddress !== null && $macAddress !== '') {
                $legacyQuery->equal('mac-address', $macAddress);
            }

            if ($ipAddress !== null && $ipAddress !== '') {
                $legacyQuery->equal('ip', $ipAddress);
            }

            Log::channel('mikrotik')->warning('Hotspot login fallback command used', [
                'username' => $username,
                'error' => $error->getMessage(),
            ]);

            return $client->query($legacyQuery)->read() ?? [];
        }
    }

    private function findActiveHotspotSession(
        Client $client,
        string $username,
        ?string $macAddress = null,
        ?string $ipAddress = null
    ): ?array {
        $query = (new Query('/ip/hotspot/active/print'))
            ->where('user', $username);

        $sessions = $client->query($query)->read() ?? [];
        if (!is_array($sessions) || $sessions === []) {
            return null;
        }

        foreach ($sessions as $session) {
            if (!is_array($session)) {
                continue;
            }

            $sessionMac = $this->normalizeMacAddressForQuery((string) ($session['mac-address'] ?? ''));
            $sessionIp = trim((string) ($session['address'] ?? ''));

            if ($macAddress !== null && $macAddress !== '' && $sessionMac !== $macAddress) {
                continue;
            }

            if ($ipAddress !== null && $ipAddress !== '' && $sessionIp !== $ipAddress) {
                continue;
            }

            return $session;
        }

        return null;
    }

    private function normalizeMacAddressForQuery(?string $value): ?string
    {
        $candidate = strtoupper(trim((string) ($value ?? '')));
        if ($candidate === '') {
            return null;
        }

        return $candidate;
    }

    /**
     * Create PPPoE session (for PPPoE users)
     */
    public function createPppoeSession(Router $router, string $username, string $password, int $durationMinutes): array
    {
        $result = $this->withRetry(function () use ($router, $username, $password, $durationMinutes) {
            $client = $this->getRequiredClient($router);
            
            // Create PPPoE secret (user)
            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $username)
                ->equal('password', $password)
                ->equal('service', 'pppoe')
                ->equal('profile', 'default') // Will be overridden by package profile
                ->equal('limit-uptime', "{$durationMinutes}m");
            
            $result = $client->query($query)->read() ?? [];
            
            Log::channel('mikrotik')->info('PPPoE session created', [
                'router' => $router->name,
                'username' => $username,
                'duration_minutes' => $durationMinutes,
            ]);
            
            return [
                'success' => true,
                'username' => $username,
                'mikrotik_response' => $result,
            ];
            
        }, $router, 'createPppoeSession');

        if (is_array($result) && isset($result['success'])) {
            return $result;
        }

        $this->pingRouter($router);
        $router = $router->fresh();

        return [
            'success' => false,
            'error' => 'Unable to create PPPoE session after retries.',
            'queued' => (string) ($router->status ?? '') === Router::STATUS_OFFLINE,
        ];
    }

    /**
     * Disconnect user session (by username or MAC)
     */
    public function disconnectSession(Router $router, string $identifier, string $type = 'username'): bool
    {
        $result = $this->withRetry(function () use ($router, $identifier, $type) {
            $client = $this->getRequiredClient($router);
            $field = $this->resolveHotspotActiveLookupField($type);
            
            // Find active session
            $query = (new Query('/ip/hotspot/active/print'))
                ->where($field, $identifier);
            
            $sessions = $client->query($query)->read() ?? [];
            
            if (empty($sessions)) {
                Log::channel('mikrotik')->warning('Session not found for disconnect', [
                    'router' => $router->name,
                    'identifier' => $identifier,
                    'type' => $type,
                    'field' => $field,
                ]);
                return false;
            }
            
            // Disconnect each matching session
            foreach ($sessions as $session) {
                $logoutQuery = (new Query('/ip/hotspot/logout'))
                    ->equal('session-id', $session['.id'] ?? $session['id'] ?? null);
                
                if ($logoutQuery) {
                    $client->query($logoutQuery)->read();
                }
            }
            
            Log::channel('mikrotik')->info('Session disconnected', [
                'router' => $router->name,
                'identifier' => $identifier,
                'type' => $type,
                'sessions_terminated' => count($sessions),
            ]);
            
            return true;
            
        }, $router, 'disconnectSession');

        return $result === true;
    }

    private function resolveHotspotActiveLookupField(string $type): string
    {
        return match (strtolower(trim($type))) {
            'user', 'username' => 'user',
            'mac', 'mac-address', 'mac_address' => 'mac-address',
            'ip', 'ip-address', 'ip_address', 'address' => 'address',
            default => trim($type) !== '' ? $type : 'user',
        };
    }

    /**
     * Get active sessions for a router
     */
    public function getActiveSessions(Router $router): array
    {
        $result = $this->withRetry(function () use ($router) {
            $client = $this->getRequiredClient($router);
            
            $query = new Query('/ip/hotspot/active/print');
            $sessions = $client->query($query)->read() ?? [];
            
            // Transform to consistent format
            return array_map(function ($session) {
                return [
                    'id' => $session['.id'] ?? $session['id'] ?? null,
                    'username' => $session['user'] ?? null,
                    'mac_address' => $session['mac-address'] ?? null,
                    'ip_address' => $session['address'] ?? null,
                    'uptime' => $session['uptime'] ?? null,
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'login_time' => $session['login-time'] ?? null,
                ];
            }, $sessions);
            
        }, $router, 'getActiveSessions');

        return is_array($result) ? $result : [];
    }

    /**
     * Sync session usage data (bytes in/out) from router to database
     */
    public function syncSessionUsage(UserSession $userSession): bool
    {
        $result = $this->withRetry(function () use ($userSession) {
            $router = $userSession->router;
            if (!$router) {
                return false;
            }
            
            $client = $this->getRequiredClient($router);
            
            // Find session on router
            $query = (new Query('/ip/hotspot/active/print'))
                ->where('user', $userSession->username);
            
            $sessions = $client->query($query)->read() ?? [];
            
            if (empty($sessions)) {
                // Session not found on router - may have been disconnected
                Log::channel('mikrotik')->warning('Session not found on router for sync', [
                    'session_id' => $userSession->id,
                    'username' => $userSession->username,
                    'router' => $router->name,
                ]);
                return false;
            }
            
            $routerSession = $sessions[0];
            
            // Update database with latest usage
            $userSession->update([
                'bytes_in' => $routerSession['bytes-in'] ?? 0,
                'bytes_out' => $routerSession['bytes-out'] ?? 0,
                'bytes_total' => ($routerSession['bytes-in'] ?? 0) + ($routerSession['bytes-out'] ?? 0),
                'mikrotik_uptime_seconds' => $this->parseUptime($routerSession['uptime'] ?? '00:00:00'),
                'last_synced_at' => now(),
                'sync_failed' => false,
            ]);
            
            return true;
            
        }, $userSession->router, 'syncSessionUsage');

        return $result === true;
    }

    /**
     * Check if router is reachable
     */
    public function pingRouter(Router $router): bool
    {
        try {
            $client = $this->getRequiredClient($router, true); // Don't cache for health checks
            
            // Simple system resource query to test connection
            $query = new Query('/system/resource/print');
            $client->query($query)->read();
            
            // Update router status
            $router->markOnline();
            $this->recordConnectivityMetadata($router, [
                'last_connectivity_check_at' => now()->toIso8601String(),
                'last_connectivity_result' => 'api_ok',
                'last_connectivity_error' => null,
                'last_connectivity_error_at' => null,
                'last_connectivity_error_type' => null,
                'api_port_reachable' => true,
                'tcp_probe_message' => null,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            [$tcpReachable, $tcpProbeMessage] = $this->probeApiTcpPort($router);
            $errorType = $this->classifyConnectivityError($e->getMessage(), $tcpReachable);

            Log::channel('mikrotik')->warning('Router ping failed', [
                'router' => $router->name,
                'ip' => $router->ip_address,
                'error' => $e->getMessage(),
                'error_type' => $errorType,
                'tcp_reachable' => $tcpReachable,
                'tcp_probe' => $tcpProbeMessage,
            ]);
            
            if ($tcpReachable) {
                // Router is reachable but MikroTik API auth/query failed.
                $router->markWarning('Router reachable but API login failed');
            } else {
                $router->markOffline();
            }

            $this->recordConnectivityMetadata($router, [
                'last_connectivity_check_at' => now()->toIso8601String(),
                'last_connectivity_result' => $tcpReachable ? 'api_failed_tcp_ok' : 'tcp_unreachable',
                'last_connectivity_error' => $e->getMessage(),
                'last_connectivity_error_at' => now()->toIso8601String(),
                'last_connectivity_error_type' => $errorType,
                'api_port_reachable' => $tcpReachable,
                'tcp_probe_message' => $tcpProbeMessage,
            ]);
            
            return false;
        }
    }

    /**
     * Read last connectivity diagnostics saved during pingRouter().
     */
    public function getConnectivityDiagnostics(Router $router): array
    {
        $metadata = is_array($router->metadata) ? $router->metadata : [];
        $errorType = $metadata['last_connectivity_error_type'] ?? null;
        $apiPortReachable = (bool) ($metadata['api_port_reachable'] ?? false);
        $rawError = (string) ($metadata['last_connectivity_error'] ?? '');
        $message = $this->buildConnectivityMessage($errorType, $apiPortReachable, $rawError);
        $service = (bool) ($router->api_ssl ?? false) ? 'api-ssl' : 'api';
        $port = (int) ($router->api_port ?: 8728);
        $host = trim((string) ($router->ip_address ?? ''));

        return [
            'status' => (string) ($router->status ?? Router::STATUS_OFFLINE),
            'last_result' => $metadata['last_connectivity_result'] ?? null,
            'error' => $metadata['last_connectivity_error'] ?? null,
            'error_type' => $errorType,
            'api_port_reachable' => $apiPortReachable,
            'tcp_probe_message' => $metadata['tcp_probe_message'] ?? null,
            'checked_at' => $metadata['last_connectivity_check_at'] ?? null,
            'message' => $message,
            'endpoint' => [
                'host' => $host,
                'port' => $port,
                'service' => $service,
                'ssl' => (bool) ($router->api_ssl ?? false),
            ],
            'hints' => $this->buildConnectivityHints($router, $errorType, $apiPortReachable, $rawError),
        ];
    }

    /**
     * Get router system info (CPU, memory, uptime)
     */
    public function getRouterSystemInfo(Router $router): array
    {
        $result = $this->withRetry(function () use ($router) {
            $client = $this->getRequiredClient($router);
            
            $query = new Query('/system/resource/print');
            $info = $client->query($query)->read();
            
            if (empty($info)) {
                return [];
            }
            
            $data = $info[0];
            $cpuLoad = $this->toPercent($data['cpu-load'] ?? null);
            $memoryUsage = $this->extractMemoryUsagePercent($data);
            
            // Update router health metrics
            $router->update([
                'cpu_usage' => $cpuLoad,
                'memory_usage' => $memoryUsage,
                'uptime_seconds' => $this->parseUptime($data['uptime'] ?? '0'),
                'last_sync_at' => now(),
            ]);
            
            return [
                'cpu_load' => $cpuLoad,
                'memory_usage' => $memoryUsage,
                'uptime' => $data['uptime'] ?? null,
                'version' => $data['version'] ?? null,
                'board_name' => $data['board-name'] ?? null,
            ];
            
        }, $router, 'getRouterSystemInfo');

        return is_array($result) ? $result : [];
    }

    private function toPercent(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $numeric = preg_replace('/[^0-9.]+/', '', (string) $value);
        if ($numeric === '' || !is_numeric($numeric)) {
            return null;
        }

        $percent = (int) round((float) $numeric);
        return max(0, min(100, $percent));
    }

    private function extractMemoryUsagePercent(array $data): ?int
    {
        $direct = $this->toPercent($data['memory-usage'] ?? null);
        if ($direct !== null) {
            return $direct;
        }

        $total = isset($data['total-memory']) ? (float) $data['total-memory'] : 0.0;
        $free = isset($data['free-memory']) ? (float) $data['free-memory'] : 0.0;

        if ($total <= 0) {
            return null;
        }

        $usedPercent = (int) round((($total - $free) / $total) * 100);
        return max(0, min(100, $usedPercent));
    }

    /**
     * Get hotspot user profiles available on the router.
     */
    public function getHotspotUserProfiles(Router $router): array
    {
        $result = $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            if (!$client) {
                return [];
            }
            $response = $client->query(new Query('/ip/hotspot/user/profile/print'))->read() ?? [];

            return collect($response)
                ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
                ->filter()
                ->values()
                ->all();
        }, $router, 'getHotspotUserProfiles');

        return is_array($result) ? $result : [];
    }

    /**
     * Get PPP profiles available on the router.
     */
    public function getPppProfiles(Router $router): array
    {
        $result = $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            if (!$client) {
                return [];
            }
            $response = $client->query(new Query('/ppp/profile/print'))->read() ?? [];

            return collect($response)
                ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
                ->filter()
                ->values()
                ->all();
        }, $router, 'getPppProfiles');

        return is_array($result) ? $result : [];
    }

    /**
     * Set session timeout on MikroTik (prevents early disconnects)
     */
    private function setSessionTimeout(Client $client, string $username, int $seconds): void
    {
        // Use hotspot user timeout (more reliable)
        $query = (new Query('/ip/hotspot/user/set'))
            ->where('name', $username)
            ->equal('limit-uptime', "{$seconds}s");
        
        $client->query($query)->read();
    }

    /**
     * Parse MikroTik uptime string to seconds
     * Format: "2d15h30m45s" or "15:30:45"
     */
    private function parseUptime(string $uptime): int
    {
        // Handle "2d15h30m45s" format
        if (preg_match('/(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/', $uptime, $matches)) {
            $days = (int)($matches[1] ?? 0);
            $hours = (int)($matches[2] ?? 0);
            $minutes = (int)($matches[3] ?? 0);
            $seconds = (int)($matches[4] ?? 0);
            
            return ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
        }
        
        // Handle "HH:MM:SS" format
        if (preg_match('/^(\d+):(\d+):(\d+)$/', $uptime, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (int)$matches[3];
            
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }
        
        return 0;
    }

    /**
     * Store connectivity check metadata without clobbering existing router metadata.
     */
    private function recordConnectivityMetadata(Router $router, array $metadata): void
    {
        $existing = is_array($router->metadata) ? $router->metadata : [];
        $router->update([
            'metadata' => array_merge($existing, $metadata),
        ]);
    }

    /**
     * Basic TCP probe to distinguish network reachability from API/auth failures.
     *
     * @return array{0: bool, 1: string}
     */
    private function probeApiTcpPort(Router $router): array
    {
        $host = trim((string) ($router->ip_address ?? ''));
        $port = (int) ($router->api_port ?: 8728);

        if ($host === '' || $port < 1 || $port > 65535) {
            return [false, 'Invalid router host/port'];
        }

        $errno = 0;
        $errstr = '';
        $start = microtime(true);

        $socket = @fsockopen($host, $port, $errno, $errstr, self::CONNECTION_TIMEOUT);
        if ($socket === false) {
            $reason = trim($errstr) !== '' ? trim($errstr) : "errno {$errno}";
            return [false, "TCP {$host}:{$port} failed ({$reason})"];
        }

        fclose($socket);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        return [true, "TCP {$host}:{$port} reachable ({$latencyMs}ms)"];
    }

    private function classifyConnectivityError(string $message, bool $tcpReachable): string
    {
        $normalized = strtolower(trim($message));

        if ($normalized === '') {
            return $tcpReachable ? 'api_error' : 'network_error';
        }

        if (
            str_contains($normalized, 'invalid user')
            || str_contains($normalized, 'cannot log in')
            || str_contains($normalized, 'not enough permissions')
            || str_contains($normalized, 'login failed')
            || str_contains($normalized, 'authentication')
            || str_contains($normalized, 'invalid username')
            || str_contains($normalized, 'invalid user name')
            || str_contains($normalized, 'username or password')
            || str_contains($normalized, 'wrong password')
            || str_contains($normalized, 'bad user')
            || str_contains($normalized, 'invalid password')
            || str_contains($normalized, 'permission denied')
        ) {
            return 'auth_error';
        }

        if (
            str_contains($normalized, 'ssl')
            || str_contains($normalized, 'tls')
            || str_contains($normalized, 'certificate')
            || str_contains($normalized, 'handshake')
            || str_contains($normalized, 'peer')
        ) {
            return 'tls_error';
        }

        if (
            str_contains($normalized, 'socket timeout reached')
            || str_contains($normalized, 'socket timeout')
            || str_contains($normalized, 'read timed out')
        ) {
            return $tcpReachable ? 'api_timeout' : 'network_error';
        }

        if (
            str_contains($normalized, 'timed out')
            || str_contains($normalized, 'no route')
            || str_contains($normalized, 'connection refused')
            || str_contains($normalized, 'unable to connect')
            || str_contains($normalized, 'network is unreachable')
            || str_contains($normalized, 'socket session')
            || str_contains($normalized, 'forbidden by its access permissions')
            || str_contains($normalized, 'actively refused')
        ) {
            return 'network_error';
        }

        return $tcpReachable ? 'api_error' : 'network_error';
    }

    /**
     * Get cached or new MikroTik API client
     */
    private function getClient(Router $router, bool $forceNew = false): ?Client
    {
        if (!$forceNew) {
            $cacheKey = "mikrotik_client:{$router->id}";
            
            return Cache::remember($cacheKey, self::CONNECTION_CACHE_TTL, function () use ($router) {
                return $this->createClient($router);
            });
        }
        
        return $this->createClient($router);
    }

    private function getRequiredClient(Router $router, bool $forceNew = false): Client
    {
        $client = $this->getClient($router, $forceNew);

        if ($client instanceof Client) {
            return $client;
        }

        $reason = trim((string) $this->lastClientInitError);
        $suffix = $reason !== '' ? " Reason: {$reason}" : '';

        throw new \RuntimeException("Could not initialize MikroTik client for router {$router->name} ({$router->ip_address}).{$suffix}");
    }

    /**
     * Create new MikroTik API client - FIXED: Removed invalid ssl_verify parameter
     */
    private function createClient(Router $router): ?Client
    {
        try {
            $config = [
                'host' => $router->ip_address,
                'user' => $router->api_username,
                'pass' => $this->resolveRouterPassword($router->api_password),
                'port' => (int) $router->api_port,
                'timeout' => self::CONNECTION_TIMEOUT,
                'socket_timeout' => self::COMMAND_TIMEOUT,
                'attempts' => 1,
            ];
            
            // SSL config - only include supported parameters
            if ($router->api_ssl) {
                $config['ssl'] = true;
                // Use ssl_options for SSL context (supported by routeros-api-php)
                $config['ssl_options'] = [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ];
            }

            $client = new Client($config);
            $this->lastClientInitError = null;

            return $client;
            
        } catch (\Throwable $e) {
            $this->lastClientInitError = $e->getMessage();
            Log::channel('mikrotik')->error('Failed to create MikroTik client', [
                'router' => $router->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildConnectivityMessage(?string $errorType, bool $apiPortReachable, string $rawError): string
    {
        return match ($errorType) {
            'auth_error' => 'Router is reachable, but MikroTik API authentication failed. Verify username/password and API permissions.',
            'tls_error' => 'Router API port is reachable, but SSL/TLS negotiation failed. Verify API SSL settings and certificates.',
            'api_timeout' => 'Router API port is reachable, but the API handshake timed out. Verify API vs API-SSL mode, allowed source addresses, and firewall rules.',
            'network_error' => 'Router API port is unreachable from the server.',
            default => $this->buildGenericConnectivityMessage($apiPortReachable, $rawError),
        };
    }

    private function buildGenericConnectivityMessage(bool $apiPortReachable, string $rawError): string
    {
        if (!$apiPortReachable) {
            return 'Router is unreachable or API is blocked.';
        }

        $error = trim($rawError);
        if ($error === '') {
            return 'Router is reachable, but MikroTik API query failed.';
        }

        if (str_contains(strtolower($error), 'socket timeout')) {
            return 'Router API port is reachable, but the API handshake timed out. Verify API vs API-SSL mode, allowed source addresses, and firewall rules.';
        }

        if (strlen($error) > 220) {
            $error = substr($error, 0, 220) . '...';
        }

        return "Router is reachable, but MikroTik API query failed: {$error}";
    }

    private function buildConnectivityHints(Router $router, ?string $errorType, bool $apiPortReachable, string $rawError): array
    {
        $host = trim((string) ($router->ip_address ?? ''));
        $port = (int) ($router->api_port ?: 8728);
        $usesSsl = (bool) ($router->api_ssl ?? false);
        $service = $usesSsl ? 'api-ssl' : 'api';
        $mode = $usesSsl ? 'API-SSL' : 'plain API';
        $hints = [];

        if ($host !== '' && $port > 0) {
            $hints[] = "CloudBridge is trying {$host}:{$port} using RouterOS {$mode}. Make sure /ip service {$service} is enabled on that exact port. Firewall allow rules do not change the RouterOS service port.";
        }

        if ($host !== '' && $this->isPrivateOrReservedIpAddress($host)) {
            $hints[] = "Saved router IP {$host} is a private or reserved address. This only works if CloudBridge can reach that LAN directly, such as from the same site or through VPN. A public server on DigitalOcean cannot reach {$host} over the open internet.";
        }

        if ($this->shouldWarnAboutLoopbackRadiusTarget($host)) {
            $radiusHost = trim((string) config('radius.server_ip', '127.0.0.1'));
            $hints[] = "RADIUS_SERVER_IP is set to {$radiusHost}. That only works when FreeRADIUS runs on the same host the router can reach directly. For a remote MikroTik, use the reachable public or VPN IP of the RADIUS server instead of loopback.";
        }

        if ($errorType === 'auth_error') {
            $username = trim((string) ($router->api_username ?? ''));
            if ($username !== '') {
                $hints[] = "CloudBridge is logging in as RouterOS user {$username}. Verify that account exists, the saved password matches, and its group includes the api permission.";
            }
        }

        if (in_array($errorType, ['api_timeout', 'tls_error'], true)) {
            if ($usesSsl) {
                $hints[] = "CloudBridge is expecting API-SSL on this router. Verify that /ip service api-ssl is enabled on port {$port} and that SSL/TLS is actually configured for RouterOS API access.";
            } else {
                $hints[] = "CloudBridge is expecting plain API on this router. If RouterOS is only exposing api-ssl or listening on another port, either enable /ip service api on port {$port} or update the saved router settings.";
            }
        }

        if ($errorType === 'network_error' && !$apiPortReachable && $host !== '' && !$this->isPrivateOrReservedIpAddress($host)) {
            $hints[] = "If this router sits behind NAT, forward TCP {$port} to the RouterOS API service or connect the site to CloudBridge over VPN before testing again.";
        }

        if ($errorType === 'api_error' && trim($rawError) !== '') {
            $hints[] = 'The router accepted the TCP connection but rejected the API request. Check the saved username, password, API mode, and any RouterOS address restrictions on the service or user account.';
        }

        return array_values(array_unique(array_filter($hints, static fn ($hint) => trim((string) $hint) !== '')));
    }

    private function isPrivateOrReservedIpAddress(string $host): bool
    {
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function shouldWarnAboutLoopbackRadiusTarget(string $routerHost): bool
    {
        if (!(bool) config('radius.enabled', false)) {
            return false;
        }

        $radiusHost = trim((string) config('radius.server_ip', ''));
        if (!$this->isLoopbackHost($radiusHost)) {
            return false;
        }

        return !$this->isLoopbackHost($routerHost);
    }

    private function isLoopbackHost(string $host): bool
    {
        return in_array(strtolower(trim($host)), ['127.0.0.1', 'localhost', '::1'], true);
    }

    /**
     * Support both encrypted and legacy plaintext stored passwords.
     */
    private function resolveRouterPassword(?string $value): string
    {
        $raw = (string) ($value ?? '');
        if ($raw === '') {
            return '';
        }

        try {
            return (string) decrypt($raw);
        } catch (DecryptException) {
            return $raw;
        }
    }

    /**
     * Execute command with retry logic and error handling
     */
    private function withRetry(callable $callback, ?Router $router, string $operation): mixed
    {
        if (!$router) {
            Log::channel('error')->error("Router is null for operation: {$operation}");
            return null;
        }

        $lastException = null;
        
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $callback();
                
            } catch (ClientException $e) {
                $lastException = $e;
                
                // Connection issues - wait and retry
                Log::channel('mikrotik')->warning("MikroTik client error (attempt {$attempt})", [
                    'router' => $router->name,
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                ]);
                
                if ($attempt < self::MAX_RETRIES) {
                    sleep(pow(2, $attempt)); // Exponential backoff: 2s, 4s
                }
                
            } catch (QueryException $e) {
                $lastException = $e;
                
                // Query/command issues - may not be retryable
                Log::channel('mikrotik')->error("MikroTik query error", [
                    'router' => $router->name,
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                ]);
                break; // Don't retry query errors
                
            } catch (ConfigException $e) {
                $lastException = $e;
                
                // Config issues - not retryable
                Log::channel('mikrotik')->error("MikroTik config error", [
                    'router' => $router->name,
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                ]);
                break;
                
            } catch (\Throwable $e) {
                $lastException = $e;
                
                // Unexpected error
                Log::channel('mikrotik')->error("Unexpected MikroTik error", [
                    'router' => $router->name,
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                if ($attempt < self::MAX_RETRIES) {
                    sleep(pow(2, $attempt));
                }
            }
        }
        
        // All retries failed
        Log::channel('error')->error("MikroTik operation failed after retries", [
            'router' => $router->name,
            'operation' => $operation,
            'error' => $lastException?->getMessage(),
        ]);
        
        return null;
    }
}
