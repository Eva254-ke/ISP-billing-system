<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory, SoftDeletes;

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
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_password',
    ];

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

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', 'online')
            ->where('cpu_usage', '<', 80)
            ->where('memory_usage', '<', 80);
    }

    public function scopeNeedsSync($query, $minutes = 5)
    {
        return $query->where('last_sync_at', '<', now()->subMinutes($minutes));
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
        return $this->status === 'online' 
            && $this->cpu_usage < 80 
            && $this->memory_usage < 80;
    }

    public function getUptimeFormattedAttribute(): string
    {
        $seconds = $this->uptime_seconds;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) return "{$days}d {$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h {$minutes}m";
        return "{$minutes}m";
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function markOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'last_seen_at' => now(),
        ]);
    }

    public function getRecommendedAccountingInterval(): int
    {
        return match($this->model) {
            'hAP lite', 'RB750r2', 'RB941-2nD' => 300, // 5 min (low RAM)
            'RB750Gr3', 'RB4011', 'CCR1009' => 60,      // 1 min (high RAM)
            default => 180, // 3 min (safe default)
        };
    }
}