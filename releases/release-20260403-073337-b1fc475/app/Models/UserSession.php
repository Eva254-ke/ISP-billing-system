<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class UserSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_sessions';

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
        'reconnect_count',
        'last_reconnected_at',
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
        'reconnect_count' => 'integer',
        'last_reconnected_at' => 'datetime',
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
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone);
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
        return $query->where(function($q) use ($minutes) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes($minutes));
        });
    }

    public function scopeForCaptivePortal($query)
    {
        return $query->whereNotNull('phone')
            ->where('status', 'active');
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
        if ($this->isInGracePeriod && $this->grace_period_ends_at) {
            return max(0, $this->grace_period_ends_at->diffInSeconds(now()));
        }
        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    public function getTimeRemainingFormattedAttribute(): string
    {
        $seconds = $this->time_remaining;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }
        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        }
        return sprintf('%ds', $secs);
    }

    public function getDataUsedMbAttribute(): float
    {
        return round($this->bytes_total / 1024 / 1024, 2);
    }

    public function getDataRemainingMbAttribute(): ?float
    {
        if (!$this->data_limit_mb) {
            return null;
        }
        return max(0, $this->data_limit_mb - $this->data_used_mb);
    }

    public function getProgressPercentageAttribute(): float
    {
        if (!$this->data_limit_mb) {
            return 0;
        }
        return min(100, round(($this->data_used_mb / $this->data_limit_mb) * 100, 1));
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL SPECIFIC METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function canReconnect(): bool
    {
        return $this->status === 'active'
            && $this->expires_at->isFuture()
            && !$this->sync_failed;
    }

    public function recordReconnect(string $method = 'manual'): void
    {
        $this->increment('reconnect_count');
        $this->update([
            'last_reconnected_at' => now(),
            'last_activity_at' => now(),
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['last_reconnect_method' => $method, 'last_reconnect_at' => now()->toIso8601String()]
            ),
        ]);

        Log::info('Session reconnected', [
            'session_id' => $this->id,
            'phone' => $this->phone,
            'method' => $method,
            'reconnect_count' => $this->reconnect_count,
        ]);
    }

    public function extendSession(int $additionalMinutes): bool
    {
        if (!$this->canReconnect()) {
            return false;
        }

        $this->expires_at = $this->expires_at->copy()->addMinutes($additionalMinutes);
        $this->last_activity_at = now();
        $this->save();

        Log::info('Session extended', [
            'session_id' => $this->id,
            'phone' => $this->phone,
            'additional_minutes' => $additionalMinutes,
            'new_expires_at' => $this->expires_at,
        ]);

        return true;
    }

    public function toArrayForCaptivePortal(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'status' => $this->status,
            'is_active' => $this->status === 'active' && !$this->is_expired,
            'is_in_grace_period' => $this->isInGracePeriod,
            'time_remaining' => $this->time_remaining,
            'time_remaining_formatted' => $this->time_remaining_formatted,
            'expires_at' => $this->expires_at->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'data_used_mb' => $this->data_used_mb,
            'data_limit_mb' => $this->data_limit_mb,
            'data_remaining_mb' => $this->data_remaining_mb,
            'progress_percentage' => $this->progress_percentage,
            'reconnect_count' => $this->reconnect_count,
            'can_reconnect' => $this->canReconnect(),
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'duration_minutes' => $this->package->duration_minutes,
                'price' => $this->package->price,
            ] : null,
        ];
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
        if ($this->isInGracePeriod) {
            return false;
        }

        if ($this->expires_at->isPast()) {
            return true;
        }

        if ($this->data_limit_mb && $this->bytes_total >= ($this->data_limit_mb * 1024 * 1024)) {
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
            'last_activity_at' => now(),
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
            'grace_period_active' => false,
        ]);
    }

    public function markSyncFailed(string $error): void
    {
        $this->increment('sync_retry_count');
        $this->update([
            'sync_failed' => true,
            'last_synced_at' => now(),
            'metadata' => array_merge(
                $this->metadata ?? [],
                [
                    'last_sync_error' => $error,
                    'last_sync_error_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        Log::warning('Session sync failed', [
            'session_id' => $this->id,
            'phone' => $this->phone,
            'error' => $error,
            'retry_count' => $this->sync_retry_count,
        ]);
    }

    public function resetSyncStatus(): void
    {
        $this->update([
            'sync_failed' => false,
            'sync_retry_count' => 0,
            'last_synced_at' => now(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS FOR CAPTIVE PORTAL
    // ──────────────────────────────────────────────────────────────────────

    public static function findActiveByPhone(string $phone): ?self
    {
        return static::byPhone($phone)
            ->active()
            ->orderBy('expires_at', 'desc')
            ->first();
    }

    public static function createFromPayment(Payment $payment, array $extra = []): self
    {
        $package = $payment->package;

        return static::create(array_merge([
            'tenant_id' => $package->tenant_id ?? null,
            'router_id' => $payment->router_id ?? null,
            'package_id' => $package->id,
            'phone' => $payment->phone,
            'username' => $payment->phone,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->copy()->addMinutes($package->duration_minutes),
            'grace_period_seconds' => config('wifi.grace_period_seconds', 300),
            'data_limit_mb' => $package->data_limit_mb,
            'mikrotik_user_profile' => $package->mikrotik_profile,
            'payment_id' => $payment->id,
            'metadata' => [
                'created_via' => 'captive_portal',
                'payment_reference' => $payment->reference,
            ],
        ], $extra));
    }

    public static function findOrCreateFromVoucher(string $voucherCode, string $phone): ?self
    {
        $voucher = Voucher::where('code', $voucherCode)
            ->where('status', 'active')
            ->where('is_used', false)
            ->first();

        if (!$voucher) {
            return null;
        }

        $voucher->update([
            'is_used' => true,
            'used_at' => now(),
            'used_by_phone' => $phone,
        ]);

        return static::createFromPayment(
            Payment::create([
                'phone' => $phone,
                'package_id' => $voucher->package_id,
                'amount' => 0,
                'status' => 'paid',
                'type' => 'voucher',
                'reference' => 'VCH-' . $voucher->code,
                'paid_at' => now(),
            ]),
            ['voucher_id' => $voucher->id]
        );
    }
}