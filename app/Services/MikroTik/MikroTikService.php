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
     * Create user session on MikroTik (Hotspot login)
     */
    public function createHotspotSession(
        Router $router,
        string $username,
        string $password,
        int $durationMinutes,
        ?string $macAddress = null,
        ?string $ipAddress = null
    ): array
    {
        return $this->withRetry(function () use ($router, $username, $password, $durationMinutes, $macAddress, $ipAddress) {
            $client = $this->getClient($router);
            
            // Calculate expiry time
            $expiresAt = now()->addMinutes($durationMinutes);
            
            // Login user to hotspot
            $result = $this->loginHotspotUser(
                client: $client,
                username: $username,
                password: $password,
                macAddress: $macAddress,
                ipAddress: $ipAddress
            );
            
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
                'mac_address' => $macAddress,
                'ip_address' => $ipAddress,
            ]);
            
            return [
                'success' => true,
                'username' => $username,
                'expires_at' => $expiresAt,
                'mikrotik_response' => $result,
            ];
            
        }, $router, 'createHotspotSession');
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
            return $client->query($query)->read();
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

            return $client->query($legacyQuery)->read();
        }
    }

    /**
     * Create PPPoE session (for PPPoE users)
     */
    public function createPppoeSession(Router $router, string $username, string $password, int $durationMinutes): array
    {
        return $this->withRetry(function () use ($router, $username, $password, $durationMinutes) {
            $client = $this->getClient($router);
            
            // Create PPPoE secret (user)
            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $username)
                ->equal('password', $password)
                ->equal('service', 'pppoe')
                ->equal('profile', 'default') // Will be overridden by package profile
                ->equal('limit-uptime', "{$durationMinutes}m");
            
            $result = $client->query($query)->read();
            
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
    }

    /**
     * Disconnect user session (by username or MAC)
     */
    public function disconnectSession(Router $router, string $identifier, string $type = 'username'): bool
    {
        return $this->withRetry(function () use ($router, $identifier, $type) {
            $client = $this->getClient($router);
            
            // Find active session
            $query = (new Query('/ip/hotspot/active/print'))
                ->where($type, $identifier);
            
            $sessions = $client->query($query)->read();
            
            if (empty($sessions)) {
                Log::channel('mikrotik')->warning('Session not found for disconnect', [
                    'router' => $router->name,
                    'identifier' => $identifier,
                    'type' => $type,
                ]);
                return false;
            }
            
            // Disconnect each matching session
            foreach ($sessions as $session) {
                $logoutQuery = (new Query('/ip/hotspot/logout'))
                    ->equal('session-id', $session['.id'] ?? $session['id']);
                
                $client->query($logoutQuery)->read();
            }
            
            Log::channel('mikrotik')->info('Session disconnected', [
                'router' => $router->name,
                'identifier' => $identifier,
                'type' => $type,
                'sessions_terminated' => count($sessions),
            ]);
            
            return true;
            
        }, $router, 'disconnectSession') ?? false;
    }

    /**
     * Get active sessions for a router
     */
    public function getActiveSessions(Router $router): array
    {
        return $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            
            $query = new Query('/ip/hotspot/active/print');
            $sessions = $client->query($query)->read();
            
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
            
        }, $router, 'getActiveSessions') ?? [];
    }

    /**
     * Sync session usage data (bytes in/out) from router to database
     */
    public function syncSessionUsage(UserSession $userSession): bool
    {
        return $this->withRetry(function () use ($userSession) {
            $router = $userSession->router;
            $client = $this->getClient($router);
            
            // Find session on router
            $query = (new Query('/ip/hotspot/active/print'))
                ->where('user', $userSession->username);
            
            $sessions = $client->query($query)->read();
            
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
            
        }, $userSession->router, 'syncSessionUsage') ?? false;
    }

    /**
     * Check if router is reachable
     */
    public function pingRouter(Router $router): bool
    {
        try {
            $client = $this->getClient($router, true); // Don't cache for health checks
            
            // Simple system resource query to test connection
            $query = new Query('/system/resource/print');
            $client->query($query)->read();
            
            // Update router status
            $router->markOnline();
            
            return true;
            
        } catch (\Exception $e) {
            Log::channel('mikrotik')->warning('Router ping failed', [
                'router' => $router->name,
                'ip' => $router->ip_address,
                'error' => $e->getMessage(),
            ]);
            
            // Update router status
            $router->markOffline();
            
            return false;
        }
    }

    /**
     * Get router system info (CPU, memory, uptime)
     */
    public function getRouterSystemInfo(Router $router): array
    {
        return $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            
            $query = new Query('/system/resource/print');
            $info = $client->query($query)->read();
            
            if (empty($info)) {
                return [];
            }
            
            $data = $info[0];
            
            // Update router health metrics
            $router->update([
                'cpu_usage' => (int) rtrim($data['cpu-load'] ?? '0', '%'),
                'memory_usage' => (int) rtrim($data['memory-usage'] ?? '0', '%'),
                'uptime_seconds' => $this->parseUptime($data['uptime'] ?? '0'),
                'last_sync_at' => now(),
            ]);
            
            return [
                'cpu_load' => rtrim($data['cpu-load'] ?? '0', '%'),
                'memory_usage' => rtrim($data['memory-usage'] ?? '0', '%'),
                'uptime' => $data['uptime'] ?? null,
                'version' => $data['version'] ?? null,
                'board_name' => $data['board-name'] ?? null,
            ];
            
        }, $router, 'getRouterSystemInfo') ?? [];
    }

    /**
     * Get hotspot user profiles available on the router.
     */
    public function getHotspotUserProfiles(Router $router): array
    {
        return $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            $response = $client->query(new Query('/ip/hotspot/user/profile/print'))->read();

            return collect($response)
                ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
                ->filter()
                ->values()
                ->all();
        }, $router, 'getHotspotUserProfiles') ?? [];
    }

    /**
     * Get PPP profiles available on the router.
     */
    public function getPppProfiles(Router $router): array
    {
        return $this->withRetry(function () use ($router) {
            $client = $this->getClient($router);
            $response = $client->query(new Query('/ppp/profile/print'))->read();

            return collect($response)
                ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
                ->filter()
                ->values()
                ->all();
        }, $router, 'getPppProfiles') ?? [];
    }

    /**
     * Set session timeout on MikroTik (prevents early disconnects)
     */
    private function setSessionTimeout(Client $client, string $username, int $seconds): void
    {
        // Use session timeout via user profile or direct limit
        // This is router-specific; adjust based on your MikroTik config
        
        // Option 1: Update user profile with timeout
        // $query = (new Query('/user/profile/set'))
        //     ->equal('name', 'default')
        //     ->equal('session-timeout', "{$seconds}s");
        // $client->query($query)->read();
        
        // Option 2: Use hotspot user timeout (more reliable)
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
     * Get cached or new MikroTik API client
     */
    private function getClient(Router $router, bool $forceNew = false): Client
    {
        if (!$forceNew) {
            $cacheKey = "mikrotik_client:{$router->id}";
            
            return Cache::remember($cacheKey, self::CONNECTION_CACHE_TTL, function () use ($router) {
                return $this->createClient($router);
            });
        }
        
        return $this->createClient($router);
    }

    /**
     * Create new MikroTik API client
     */
    private function createClient(Router $router): Client
    {
        $config = [
            'host' => $router->ip_address,
            'user' => $router->api_username,
            'pass' => $this->resolveRouterPassword($router->api_password),
            'port' => $router->api_port,
            'timeout' => self::CONNECTION_TIMEOUT,
            'attempts' => 1,
        ];
        
        if ($router->api_ssl) {
            $config['ssl'] = true;
            $config['ssl_verify'] = false; // Disable for self-signed certs (common in MikroTik)
        }
        
        return new Client($config);
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
    private function withRetry(callable $callback, Router $router, string $operation): mixed
    {
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
                
            } catch (\Exception $e) {
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
