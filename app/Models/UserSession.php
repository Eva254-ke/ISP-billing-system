<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_sessions'; // Explicit table name

    protected $fillable = [
        'tenant_id',
        'router_id',
        'package_id',
        'username',
        'phone',
        'mac_address',
        'ip_address',
        'status',
        'started_at',
        'expires_at',
        'last_activity_at',
        'terminated_at',
        'termination_reason',
        'grace_period_active',
        'grace_period_ends_at',
        'grace_period_seconds',
        'bytes_in',
        'bytes_out',
        'bytes_total',
        'data_limit_mb',
        'mikrotik_session_id',
        'mikrotik_user_profile',
        'mikrotik_uptime_seconds',
        'payment_id',
        'voucher_id',
        'last_synced_at',
        'sync_failed',
        'sync_retry_count',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'terminated_at' => 'datetime',
        'grace_period_active' => 'boolean',
        'grace_period_ends_at' => 'datetime',
        'grace_period_seconds' => 'integer',
        'bytes_in' => 'integer',
        'bytes_out' => 'integer',
        'bytes_total' => 'integer',
        'data_limit_mb' => 'integer',
        'mikrotik_uptime_seconds' => 'integer',
        'sync_failed' => 'boolean',
        'sync_retry_count' => 'integer',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $minutes = 10)
    {
        return $query->where('expires_at', '<=', now()->addMinutes($minutes))
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'active');
    }

    public function scopeInGracePeriod($query)
    {
        return $query->where('grace_period_active', true)
            ->where('grace_period_ends_at', '>', now());
    }

    public function scopeNeedsSync($query, $minutes = 5)
    {
        return $query->where('last_synced_at', '<', now()->subMinutes($minutes));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast() && !$this->grace_period_active;
    }

    public function getIsInGracePeriodAttribute(): bool
    {
        return $this->grace_period_active 
            && $this->grace_period_ends_at 
            && $this->grace_period_ends_at->isFuture();
    }

    public function getTimeRemainingAttribute(): int
    {
        if ($this->isInGracePeriod) {
            return $this->expires_at->diffInSeconds(now()) + $this->grace_period_seconds;
        }
        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    public function getTimeRemainingFormattedAttribute(): string
    {
        $seconds = $this->time_remaining;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) return "{$hours}h {$minutes}m";
        return "{$minutes}m";
    }

    public function getDataUsedMbAttribute(): float
    {
        return round($this->bytes_total / 1024 / 1024, 2);
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function activateGracePeriod(): void
    {
        $this->update([
            'grace_period_active' => true,
            'grace_period_ends_at' => now()->addSeconds($this->grace_period_seconds),
        ]);
    }

    public function deactivateGracePeriod(): void
    {
        $this->update([
            'grace_period_active' => false,
            'grace_period_ends_at' => null,
        ]);
    }

    public function shouldDisconnect(): bool
    {
        // Don't disconnect during grace period
        if ($this->isInGracePeriod) {
            return false;
        }

        // Disconnect if expired
        if ($this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    public function updateUsage(int $bytesIn, int $bytesOut): void
    {
        $this->update([
            'bytes_in' => $bytesIn,
            'bytes_out' => $bytesOut,
            'bytes_total' => $bytesIn + $bytesOut,
            'last_synced_at' => now(),
            'sync_failed' => false,
        ]);
    }

    public function markTerminated(string $reason): void
    {
        $this->update([
            'status' => 'terminated',
            'terminated_at' => now(),
            'termination_reason' => $reason,
        ]);
    }
}