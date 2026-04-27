<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Exception\QueryException;
use RouterOS\Exception\ClientException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class Router extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Cache table-column existence checks to avoid repeated schema queries.
     *
     * @var array<string, bool>
     */
    protected static array $columnExistenceCache = [];

    protected $fillable = [
        'tenant_id',
        'name',
        'model',
        'serial_number',
        'ip_address',
        'api_port',
        'api_username',
        'api_password',
        'api_ssl',
        'location',
        'latitude',
        'longitude',
        'status',
        'last_seen_at',
        'last_sync_at',
        'cpu_usage',
        'memory_usage',
        'active_sessions',
        'uptime_seconds',
        'accounting_interval',
        'ntp_enabled',
        'ntp_server',
        'config_backup',
        'notes',
        'captive_portal_enabled',
        'captive_portal_url',
        'walled_garden_domains',
        'default_hotspot_profile',
        'radius_enabled',
        'radius_secret',
        'radius_accounting_enabled',
        'radius_nas_id',
        'auto_sync_enabled',
        'sync_on_payment',
        'metadata',
    ];

    protected $casts = [
        'api_port' => 'integer',
        'api_ssl' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'cpu_usage' => 'integer',
        'memory_usage' => 'integer',
        'active_sessions' => 'integer',
        'uptime_seconds' => 'integer',
        'accounting_interval' => 'integer',
        'ntp_enabled' => 'boolean',
        'config_backup' => 'array',
        'walled_garden_domains' => 'array',
        'captive_portal_enabled' => 'boolean',
        'radius_enabled' => 'boolean',
        'radius_accounting_enabled' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'sync_on_payment' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_password',
        'radius_secret',
    ];

    protected $appends = [
        'connection_status',
        'health_status',
        'captive_portal_url',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────────────

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    const HEALTHY_CPU_THRESHOLD = 80;
    const HEALTHY_MEMORY_THRESHOLD = 80;
    const SYNC_TIMEOUT_SECONDS = 10;

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function routerConfigs(): HasMany
    {
        return $this->hasMany(RouterConfig::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', self::STATUS_OFFLINE);
    }

    public function scopeWarning($query)
    {
        return $query->where('status', self::STATUS_WARNING);
    }

    public function scopeOperational($query)
    {
        return $query->whereIn('status', [self::STATUS_ONLINE, self::STATUS_WARNING]);
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', self::STATUS_ONLINE)
            ->where('cpu_usage', '<', self::HEALTHY_CPU_THRESHOLD)
            ->where('memory_usage', '<', self::HEALTHY_MEMORY_THRESHOLD);
    }

    public function scopeNeedsSync($query, $minutes = 5)
    {
        return $query->where(function($q) use ($minutes) {
            $q->whereNull('last_sync_at')
                ->orWhere('last_sync_at', '<', now()->subMinutes($minutes));
        });
    }

    public function scopeWithCaptivePortal($query)
    {
        return $query->online()
            ->where('captive_portal_enabled', true);
    }

    public function scopeWithRadius($query)
    {
        return $query->online()
            ->where('radius_enabled', true);
    }

    public function scopeByLocation($query, $latitude, $longitude, $radiusKm = 10)
    {
        // Simple bounding box approximation for location-based queries
        $latRange = $radiusKm / 111;
        $lngRange = $radiusKm / (111 * cos(deg2rad($latitude)));
        
        return $query->whereBetween('latitude', [$latitude - $latRange, $latitude + $latRange])
            ->whereBetween('longitude', [$longitude - $lngRange, $longitude + $lngRange]);
    }

    /**
     * Resolve de-duplicated, ranked router candidates for a tenant.
     *
     * Newer records win inside obvious duplicate groups so stale rows do not
     * keep being auto-selected after credentials, ports, or public IP routing
     * have been corrected.
     *
     * @return Collection<int, self>
     */
    public static function selectionCandidatesForTenant(?int $tenantId): Collection
    {
        $tenantId = (int) $tenantId;
        if ($tenantId <= 0) {
            return collect();
        }

        $routers = static::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return static::rankRoutersForSelection(
            static::collapseStaleDuplicateRouters($routers)
        );
    }

    public static function resolvePreferredForTenant(?int $tenantId, bool $preferOperational = true): ?self
    {
        $candidates = static::selectionCandidatesForTenant($tenantId);
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($preferOperational) {
            $operational = $candidates->filter(fn (self $router): bool => $router->hasOperationalStatus())->values();
            if ($operational->isNotEmpty()) {
                return $operational->first();
            }
        }

        return $candidates->first();
    }

    public static function resolvePreferredIdForTenant(?int $tenantId, bool $preferOperational = true): ?int
    {
        return static::resolvePreferredForTenant($tenantId, $preferOperational)?->id;
    }

    public static function resolveCanonicalRecord(?self $router): ?self
    {
        if (!$router instanceof self) {
            return null;
        }

        $tenantId = (int) ($router->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return $router;
        }

        $targetFingerprint = $router->duplicateSelectionFingerprint();

        foreach (static::selectionCandidatesForTenant($tenantId) as $candidate) {
            if ($candidate->duplicateSelectionFingerprint() === $targetFingerprint) {
                return $candidate;
            }
        }

        return $router;
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getApiUrlAttribute(): string
    {
        $protocol = $this->api_ssl ? 'ssl' : 'http';
        return "{$protocol}://{$this->ip_address}:{$this->api_port}";
    }

    public function getIsHealthyAttribute(): bool
    {
        return $this->status === self::STATUS_ONLINE 
            && $this->cpu_usage < self::HEALTHY_CPU_THRESHOLD 
            && $this->memory_usage < self::HEALTHY_MEMORY_THRESHOLD;
    }

    public function getUptimeFormattedAttribute(): string
    {
        $seconds = $this->uptime_seconds ?? 0;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) return "{$days}d {$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h {$minutes}m";
        return "{$minutes}m";
    }

    public function getConnectionStatusAttribute(): string
    {
        if (!$this->isRouterActive()) {
            return 'inactive';
        }
        
        if ($this->status === self::STATUS_ERROR) {
            return 'error';
        }
        
        if ($this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(2))) {
            return 'online';
        }
        
        if ($this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(10))) {
            return 'warning';
        }
        
        return 'offline';
    }

    public function getHealthStatusAttribute(): string
    {
        if ($this->status !== self::STATUS_ONLINE) {
            return 'offline';
        }
        
        if ($this->cpu_usage >= 90 || $this->memory_usage >= 90) {
            return 'critical';
        }
        
        if ($this->cpu_usage >= self::HEALTHY_CPU_THRESHOLD || $this->memory_usage >= self::HEALTHY_MEMORY_THRESHOLD) {
            return 'warning';
        }
        
        return 'healthy';
    }

    public function hasOperationalStatus(): bool
    {
        return in_array((string) $this->status, [self::STATUS_ONLINE, self::STATUS_WARNING], true);
    }

    private static function collapseStaleDuplicateRouters(Collection $routers): Collection
    {
        $canonical = [];

        foreach (static::sortRoutersByFreshness($routers) as $router) {
            $key = $router->duplicateSelectionFingerprint();

            if (!array_key_exists($key, $canonical)) {
                $canonical[$key] = $router;
            }
        }

        return collect(array_values($canonical));
    }

    private static function rankRoutersForSelection(Collection $routers): Collection
    {
        return $routers
            ->sort(fn (self $left, self $right): int => static::compareSelectionPriority($left, $right))
            ->values();
    }

    private static function sortRoutersByFreshness(Collection $routers): Collection
    {
        return $routers
            ->sort(fn (self $left, self $right): int => static::compareFreshness($left, $right))
            ->values();
    }

    private static function compareSelectionPriority(self $left, self $right): int
    {
        $status = static::selectionStatusRank($left) <=> static::selectionStatusRank($right);
        if ($status !== 0) {
            return $status;
        }

        $lastSeen = static::compareNullableTimestamps($left->last_seen_at?->getTimestamp(), $right->last_seen_at?->getTimestamp());
        if ($lastSeen !== 0) {
            return $lastSeen;
        }

        return static::compareFreshness($left, $right);
    }

    private static function compareFreshness(self $left, self $right): int
    {
        $created = static::compareNullableTimestamps($left->created_at?->getTimestamp(), $right->created_at?->getTimestamp());
        if ($created !== 0) {
            return $created;
        }

        return (int) $right->id <=> (int) $left->id;
    }

    private static function compareNullableTimestamps(?int $left, ?int $right): int
    {
        return ($right ?? 0) <=> ($left ?? 0);
    }

    private static function selectionStatusRank(self $router): int
    {
        return match ((string) $router->status) {
            self::STATUS_ONLINE => 0,
            self::STATUS_WARNING => 1,
            self::STATUS_OFFLINE => 2,
            self::STATUS_ERROR => 3,
            default => 4,
        };
    }

    private function duplicateSelectionFingerprint(): string
    {
        $serial = trim((string) ($this->serial_number ?? ''));
        if ($serial !== '') {
            return 'serial:' . strtolower($serial);
        }

        $nasId = trim((string) ($this->radius_nas_id ?? ''));
        if ($nasId !== '') {
            return 'nas:' . strtolower($nasId);
        }

        $ipAddress = strtolower(trim((string) ($this->ip_address ?? '')));
        if ($ipAddress !== '' && $this->isPublicIpAddress($ipAddress)) {
            return 'public-ip:' . $ipAddress;
        }

        return sprintf(
            'endpoint:%s:%d:%d',
            $ipAddress,
            (int) ($this->api_port ?? 8728),
            (bool) ($this->api_ssl ?? false) ? 1 : 0
        );
    }

    private function isPublicIpAddress(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    public function getCaptivePortalUrlAttribute(): string
    {
        return $this->attributes['captive_portal_url']
            ?? config('app.url') . '/wifi';
    }

    public function getWalledGardenRulesAttribute(): array
    {
        $domains = $this->walled_garden_domains ?? [];
        $portalHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url((string) ($this->attributes['captive_portal_url'] ?? ''), PHP_URL_HOST),
        ]);
        $default = [
            ...$portalHosts,
            'cloudbridge.network',
            '*.cloudbridge.network',
            'mpesa.co.ke',
            'safaricom.co.ke',
            '*.safaricom.co.ke',
            'api.safaricom.co.ke',
            'sandbox.safaricom.co.ke',
            '*.googleapis.com',
            '*.gstatic.com',
        ];
        
        return array_values(array_filter(array_unique(array_merge($default, $domains))));
    }

    public function getRadiusNasIdentifierAttribute(): string
    {
        return $this->radius_nas_id ?? $this->ip_address ?? $this->serial_number ?? 'cloudbridge-' . $this->id;
    }

    // ──────────────────────────────────────────────────────────────────────
    // ROUTEROS CLIENT
    // ──────────────────────────────────────────────────────────────────────

    public function getClient(array $options = []): ?Client
    {
        $cacheKey = "router_client_{$this->id}";
        
        if ($options['use_cache'] ?? true) {
            return Cache::remember($cacheKey, 300, function () use ($options) {
                return $this->createClient($options);
            });
        }
        
        return $this->createClient($options);
    }

    protected function createClient(array $options = []): ?Client
    {
        try {
            return new Client([
                'host' => $this->ip_address,
                'port' => $options['port'] ?? $this->api_port ?? 8728,
                'user' => $this->api_username,
                'pass' => $this->resolveApiPassword(),
                'ssl' => $this->api_ssl ?? false,
                'timeout' => $options['timeout'] ?? self::SYNC_TIMEOUT_SECONDS,
                'attempts' => $options['attempts'] ?? 2,
                'delay' => $options['delay'] ?? 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create RouterOS client', [
                'router_id' => $this->id,
                'ip' => $this->ip_address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    protected function resolveApiPassword(): string
    {
        $raw = (string) ($this->api_password ?? '');
        if ($raw === '') {
            return '';
        }

        try {
            return (string) decrypt($raw);
        } catch (DecryptException) {
            return $raw;
        }
    }

    protected function executeQuery(Query $query, array $options = []): array
    {
        $client = $this->getClient($options);
        
        if (!$client) {
            throw new \RuntimeException("Could not connect to router {$this->name}");
        }

        try {
            return $client->query($query)->read();
        } catch (QueryException $e) {
            Log::error('RouterOS query failed', [
                'router_id' => $this->id,
                'query' => $query->getQuery(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (ClientException $e) {
            $this->markConnectionError($e->getMessage());
            throw $e;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function addHotspotUser(string $username, string $password, Package $package, array $extra = []): bool
    {
        try {
            $params = $package->getMikroTikUserParams($username, $password);
            $params = array_merge($params, $extra);

            $query = new Query('/ip/hotspot/user/add');
            foreach ($params as $key => $value) {
                $query->equal($key, $value);
            }

            $this->executeQuery($query);
            
            $this->update(['last_sync_at' => now()]);
            
            Log::info('Hotspot user added', [
                'router_id' => $this->id,
                'router_name' => $this->name,
                'username' => $username,
                'package' => $package->name,
                'profile' => $params['profile'] ?? null,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->markConnectionError($e->getMessage());
            
            Log::error('Failed to add hotspot user', [
                'router_id' => $this->id,
                'username' => $username,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function removeHotspotUser(string $username): bool
    {
        try {
            $query = (new Query('/ip/hotspot/user/remove'))
                ->equal('name', $username);

            $this->executeQuery($query);
            
            Log::info('Hotspot user removed', [
                'router_id' => $this->id,
                'username' => $username,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove hotspot user', [
                'router_id' => $this->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function disconnectUser(string $username): bool
    {
        try {
            $query = (new Query('/ip/hotspot/active/remove'))
                ->equal('user', $username);

            $this->executeQuery($query);
            
            Log::info('User disconnected', [
                'router_id' => $this->id,
                'username' => $username,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to disconnect user', [
                'router_id' => $this->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function getUserSession(string $username): ?array
    {
        try {
            $query = (new Query('/ip/hotspot/active/print'))
                ->equal('user', $username);

            $result = $this->executeQuery($query);
            
            return $result[0] ?? null;
            
        } catch (\Exception $e) {
            Log::warning('Failed to fetch user session', [
                'router_id' => $this->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    public function getActiveSessions(array $filters = []): array
    {
        try {
            $query = new Query('/ip/hotspot/active/print');
            
            if (!empty($filters['user'])) {
                $query->equal('user', $filters['user']);
            }
            if (!empty($filters['address'])) {
                $query->equal('address', $filters['address']);
            }
            if (!empty($filters['limit'])) {
                $query->equal('.proplist', 'user,address,uptime,bytes-in,bytes-out');
            }

            $result = $this->executeQuery($query);
            
            return $result ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch active sessions', [
                'router_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    public function syncActiveSessionsCount(): int
    {
        $sessions = $this->getActiveSessions();
        $count = count($sessions);
        
        $this->update([
            'active_sessions' => $count,
            'last_sync_at' => now(),
        ]);
        
        return $count;
    }

    public function syncSystemResources(): bool
    {
        try {
            $query = new Query('/system/resource/print');
            $result = $this->executeQuery($query);
            $resource = $result[0] ?? [];
            $cpuUsage = $this->toPercent($resource['cpu-load'] ?? null);
            $memoryUsage = $this->extractMemoryUsagePercent($resource);
            
            $this->update([
                'cpu_usage' => $cpuUsage,
                'memory_usage' => $memoryUsage,
                'uptime_seconds' => $this->parseUptime($resource['uptime'] ?? '0s'),
                'last_sync_at' => now(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to sync system resources', [
                'router_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    protected function toPercent(mixed $value): ?int
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

    protected function extractMemoryUsagePercent(array $resource): ?int
    {
        $direct = $this->toPercent($resource['memory-usage'] ?? null);
        if ($direct !== null) {
            return $direct;
        }

        $total = isset($resource['total-memory']) ? (float) $resource['total-memory'] : 0.0;
        $free = isset($resource['free-memory']) ? (float) $resource['free-memory'] : 0.0;

        if ($total <= 0) {
            return null;
        }

        $usedPercent = (int) round((($total - $free) / $total) * 100);
        return max(0, min(100, $usedPercent));
    }

    protected function parseUptime(string $uptime): int
    {
        $total = 0;
        $parts = explode(',', $uptime);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^(\d+)d$/', $part, $m)) {
                $total += $m[1] * 86400;
            } elseif (preg_match('/^(\d+)h$/', $part, $m)) {
                $total += $m[1] * 3600;
            } elseif (preg_match('/^(\d+)m$/', $part, $m)) {
                $total += $m[1] * 60;
            } elseif (preg_match('/^(\d+)s$/', $part, $m)) {
                $total += $m[1];
            }
        }
        
        return $total;
    }

    public function syncWalledGarden(): bool
    {
        try {
            $rules = $this->walled_garden_rules;
            $client = $this->getClient();
            
            if (!$client) {
                return false;
            }
            
            foreach ($rules as $domain) {
                $query = (new Query('/ip/hotspot/walled-garden/add'))
                    ->equal('dst-host', $domain)
                    ->equal('action', 'allow');
                
                $client->query($query)->read();
            }
            
            Log::info('Walled garden synced', [
                'router_id' => $this->id,
                'rules_count' => count($rules),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to sync walled garden', [
                'router_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        
        try {
            $client = $this->getClient(['timeout' => 5, 'use_cache' => false]);
            
            if (!$client) {
                return [
                    'success' => false,
                    'error' => 'Could not create client',
                    'message' => 'Connection failed',
                    'latency_ms' => null,
                ];
            }
            
            $client->query(new Query('/system/resource/print'))->read();
            $latency = round((microtime(true) - $start) * 1000);
            
            $this->update([
                'status' => self::STATUS_ONLINE,
                'last_seen_at' => now(),
                'last_sync_at' => now(),
            ]);
            
            return [
                'success' => true,
                'latency_ms' => $latency,
                'message' => 'Connection successful',
                'router' => $this->toArrayForCaptivePortal(),
            ];
            
        } catch (\Exception $e) {
            $this->markConnectionError($e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Connection failed',
                'latency_ms' => null,
            ];
        }
    }

    protected function markConnectionError(string $error): void
    {
        $this->persistRouterState([
            'status' => self::STATUS_ERROR,
            'metadata' => array_merge(
                $this->metadata ?? [],
                [
                    'last_error' => $error,
                    'last_error_at' => now()->toIso8601String(),
                ]
            ),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // RADIUS METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function configureRadiusClient(): bool
    {
        if (!$this->radius_enabled) {
            return false;
        }
        
        try {
            $client = $this->getClient();
            if (!$client) {
                return false;
            }
            
            $query = (new Query('/radius/add'))
                ->equal('address', config('radius.server_ip'))
                ->equal('secret', $this->radius_secret)
                ->equal('service', 'hotspot')
                ->equal('timeout', '3s')
                ->equal('src-address', $this->ip_address);
            
            $client->query($query)->read();
            
            if ($this->radius_accounting_enabled) {
                $query = (new Query('/radius/add'))
                    ->equal('address', config('radius.server_ip'))
                    ->equal('secret', $this->radius_secret)
                    ->equal('service', 'accounting')
                    ->equal('timeout', '3s');
                
                $client->query($query)->read();
            }
            
            Log::info('RADIUS client configured', [
                'router_id' => $this->id,
                'server' => config('radius.server_ip'),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to configure RADIUS client', [
                'router_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATE MANAGEMENT
    // ──────────────────────────────────────────────────────────────────────

    public function markOnline(): void
    {
        $this->persistRouterState([
            'status' => self::STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->persistRouterState([
            'status' => self::STATUS_OFFLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markWarning(?string $reason = null): void
    {
        $this->persistRouterState([
            'status' => self::STATUS_WARNING,
            'last_seen_at' => now(),
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['warning_reason' => $reason, 'warning_at' => now()->toIso8601String()]
            ),
        ]);
    }

    public function shouldAutoSync(): bool
    {
        return $this->auto_sync_enabled 
            && $this->isRouterActive()
            && $this->status === self::STATUS_ONLINE;
    }

    protected function isRouterActive(): bool
    {
        if (array_key_exists('is_active', $this->attributes)) {
            return (bool) $this->attributes['is_active'];
        }

        return true;
    }

    protected function persistRouterState(array $changes): void
    {
        $changes = $this->filterPersistableColumns($changes);
        if (empty($changes)) {
            return;
        }

        if (!$this->exists) {
            $this->forceFill($changes);
            return;
        }

        static::query()->whereKey($this->getKey())->update($changes);
        $this->forceFill($changes)->syncOriginalAttributes(array_keys($changes));
    }

    protected function filterPersistableColumns(array $changes): array
    {
        $filtered = [];
        $dropped = [];

        foreach ($changes as $column => $value) {
            if ($this->hasTableColumn((string) $column)) {
                $filtered[$column] = $value;
                continue;
            }

            $dropped[] = (string) $column;
        }

        if (!empty($dropped)) {
            Log::channel('mikrotik')->warning('Skipped unsupported router columns during state persistence', [
                'router_id' => $this->id,
                'table' => $this->getTable(),
                'columns' => $dropped,
            ]);
        }

        return $filtered;
    }

    protected function hasTableColumn(string $column): bool
    {
        $cacheKey = $this->getTable() . ':' . $column;

        if (!array_key_exists($cacheKey, self::$columnExistenceCache)) {
            try {
                self::$columnExistenceCache[$cacheKey] = Schema::hasColumn($this->getTable(), $column);
            } catch (\Throwable $e) {
                // Fail open if schema inspection is unavailable to avoid blocking status updates.
                Log::channel('mikrotik')->warning('Unable to inspect router table schema; assuming column exists', [
                    'router_id' => $this->id,
                    'table' => $this->getTable(),
                    'column' => $column,
                    'error' => $e->getMessage(),
                ]);
                self::$columnExistenceCache[$cacheKey] = true;
            }
        }

        return self::$columnExistenceCache[$cacheKey];
    }

    public function getRecommendedAccountingInterval(): int
    {
        return match($this->model) {
            'hAP lite', 'RB750r2', 'RB941-2nD' => 300,
            'RB750Gr3', 'RB4011', 'CCR1009' => 60,
            default => 180,
        };
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL OUTPUT
    // ──────────────────────────────────────────────────────────────────────

    public function toArrayForCaptivePortal(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'location' => $this->location,
            'ip_address' => $this->ip_address,
            'captive_portal_url' => $this->captive_portal_url,
            'walled_garden' => $this->walled_garden_rules,
            'is_active' => $this->is_active ?? true,
            'connection_status' => $this->connection_status,
            'health_status' => $this->health_status,
            'cpu_usage' => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'active_sessions' => $this->active_sessions,
            'uptime_formatted' => $this->uptime_formatted,
            'radius_enabled' => $this->radius_enabled,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────

    public static function findActiveForLocation(float $lat, float $lng, float $radiusKm = 10): array
    {
        return static::withCaptivePortal()
            ->byLocation($lat, $lng, $radiusKm)
            ->orderBy('active_sessions', 'desc')
            ->get()
            ->toArray();
    }

    public static function findBestRouterForCaptivePortal(?string $phone = null): ?self
    {
        return static::withCaptivePortal()
            ->healthy()
            ->orderBy('active_sessions', 'asc')
            ->first();
    }

    public static function syncAllOnlineRouters(): int
    {
        $count = 0;
        
        static::online()->chunk(10, function ($routers) use (&$count) {
            foreach ($routers as $router) {
                if ($router->shouldAutoSync()) {
                    $router->syncSystemResources();
                    $router->syncActiveSessionsCount();
                    $count++;
                }
            }
        });
        
        return $count;
    }
}
