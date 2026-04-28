<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'package_id',
        'code',
        'prefix',
        'status',
        'used_at',
        'used_by_phone',
        'used_by_mac',
        'used_by_ip',
        'router_id',
        'valid_from',
        'valid_until',
        'validity_hours',
        'batch_id',
        'batch_name',
        'printed',
        'printed_at',
        'captive_portal_redeemable',
        'redeemed_via',
        'redeemed_at',
        'max_redemptions',
        'redemption_count',
        'metadata',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'printed' => 'boolean',
        'printed_at' => 'datetime',
        'metadata' => 'array',
        'validity_hours' => 'integer',
        'captive_portal_redeemable' => 'boolean',
        'max_redemptions' => 'integer',
        'redemption_count' => 'integer',
        'redeemed_at' => 'datetime',
    ];

    protected $appends = [
        'code_display',
        'is_expired',
        'is_usable',
        'is_valid_for_captive_portal',
        'remaining_time',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────────────

    const STATUS_UNUSED = 'unused';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    const REDEEMED_VIA_ADMIN = 'admin';
    const REDEEMED_VIA_CAPTIVE_PORTAL = 'captive_portal';
    const REDEEMED_VIA_API = 'api';
    const REDEEMED_VIA_POS = 'pos';

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class, 'voucher_id');
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeUnused($query)
    {
        return $query->where('status', self::STATUS_UNUSED);
    }

    public function scopeUsed($query)
    {
        return $query->where('status', self::STATUS_USED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere('valid_until', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_UNUSED)
            ->where('valid_until', '>', now())
            ->where(function($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            });
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    public function scopeForCaptivePortal($query)
    {
        return $query->valid()
            ->where('captive_portal_redeemable', true)
            ->where(function($q) {
                $q->whereNull('max_redemptions')
                    ->orWhereRaw('redemption_count < max_redemptions');
            })
            ->with(['package' => function($q) {
                $q->select('id', 'name', 'price', 'duration_value', 'duration_unit', 'data_limit_mb');
            }]);
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('used_by_phone', $phone);
    }

    public function scopeRecentlyUsed($query, $minutes = 60)
    {
        return $query->used()
            ->where('used_at', '>=', now()->subMinutes($minutes));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getCodeDisplayAttribute(): string
    {
        $code = strtoupper(trim((string) $this->code));
        $prefix = static::normalizePrefix($this->prefix);

        if ($code === '') {
            return $prefix ?? '';
        }

        if ($prefix === null) {
            return $code;
        }

        if (str_starts_with($code, $prefix . '-')) {
            return $code;
        }

        return $prefix . '-' . $code;
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getIsUsableAttribute(): bool
    {
        if ($this->status !== self::STATUS_UNUSED) {
            return false;
        }
        if ($this->is_expired) {
            return false;
        }
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }
        if ($this->max_redemptions && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }
        return true;
    }

    public function getIsValidForCaptivePortalAttribute(): bool
    {
        return $this->is_usable
            && $this->captive_portal_redeemable
            && $this->package
            && $this->package->is_active;
    }

    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }
        return max(0, $this->valid_until->diffInSeconds(now()));
    }

    public function getRemainingTimeFormattedAttribute(): ?string
    {
        if (!$this->remaining_time) {
            return null;
        }
        $seconds = $this->remaining_time;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    public function getValidityWindowAttribute(): array
    {
        return [
            'from' => $this->valid_from?->toIso8601String(),
            'until' => $this->valid_until?->toIso8601String(),
            'hours' => $this->validity_hours,
            'is_active' => $this->is_usable,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function redeemForCaptivePortal(
        string $phone,
        ?string $mac = null,
        ?string $ip = null,
        ?int $routerId = null
    ): array {
        $cacheKey = "voucher_redeem_{$this->id}_{$phone}";
        
        return Cache::lock($cacheKey, 10)->get(function () use ($phone, $mac, $ip, $routerId) {
            if (!$this->is_valid_for_captive_portal) {
                return $this->buildRedeemErrorResponse();
            }

            $this->refresh();
            
            if (!$this->is_valid_for_captive_portal) {
                return [
                    'success' => false,
                    'error' => 'already_redeemed',
                    'message' => 'Voucher was just redeemed. Please try again.',
                ];
            }

            $redemptionData = [
                'status' => self::STATUS_USED,
                'used_at' => now(),
                'used_by_phone' => $phone,
                'used_by_mac' => $mac,
                'used_by_ip' => $ip,
                'router_id' => $routerId,
                'redeemed_via' => self::REDEEMED_VIA_CAPTIVE_PORTAL,
                'redeemed_at' => now(),
                'redemption_count' => $this->redemption_count + 1,
                'metadata' => array_merge(
                    $this->metadata ?? [],
                    [
                        'redeemed_via' => self::REDEEMED_VIA_CAPTIVE_PORTAL,
                        'redeemed_at' => now()->toIso8601String(),
                        'client_mac' => $mac,
                        'client_ip' => $ip,
                        'router_id' => $routerId,
                    ]
                ),
            ];

            $this->update($redemptionData);

            $session = $this->createSessionForPhone($phone, $routerId, $mac, $ip);

            Log::info('Voucher redeemed via captive portal', [
                'voucher_id' => $this->id,
                'code' => $this->code_display,
                'phone' => $phone,
                'package' => $this->package?->name,
                'router_id' => $routerId,
                'session_created' => $session ? true : false,
            ]);

            return [
                'success' => true,
                'message' => 'Voucher redeemed successfully',
                'voucher' => $this->toArrayForCaptivePortal(),
                'package' => $this->package?->toArrayForCaptivePortal(),
                'session' => $session?->toArrayForCaptivePortal(),
            ];
        });
    }

    protected function buildRedeemErrorResponse(): array
    {
        if ($this->status !== self::STATUS_UNUSED) {
            return [
                'success' => false,
                'error' => 'already_used',
                'message' => 'This voucher has already been used',
                'used_at' => $this->used_at?->toIso8601String(),
            ];
        }

        if ($this->is_expired) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'This voucher has expired',
                'expired_at' => $this->valid_until?->toIso8601String(),
            ];
        }

        if (!$this->captive_portal_redeemable) {
            return [
                'success' => false,
                'error' => 'not_redeemable_via_portal',
                'message' => 'This voucher cannot be redeemed via WiFi portal',
            ];
        }

        if ($this->max_redemptions && $this->redemption_count >= $this->max_redemptions) {
            return [
                'success' => false,
                'error' => 'max_redemptions_reached',
                'message' => 'This voucher has reached its maximum usage limit',
            ];
        }

        return [
            'success' => false,
            'error' => 'unknown',
            'message' => 'Voucher cannot be redeemed',
        ];
    }

    protected function createSessionForPhone(
        string $phone,
        ?int $routerId = null,
        ?string $mac = null,
        ?string $ip = null
    ): ?UserSession {
        if (!$this->package) {
            return null;
        }

        $payment = Payment::create([
            'tenant_id' => $this->tenant_id,
            'phone' => $phone,
            'package_id' => $this->package_id,
            'package_name' => $this->package->name,
            'amount' => 0,
            'currency' => 'KES',
            'status' => Payment::STATUS_COMPLETED,
            'type' => Payment::TYPE_VOUCHER,
            'reference' => 'VCH-' . $this->code_display,
            'paid_at' => now(),
            'completed_at' => now(),
            'activated_at' => now(),
            'metadata' => [
                'voucher_id' => $this->id,
                'redeemed_via' => 'captive_portal',
            ],
        ]);

        return UserSession::createFromPayment($payment, [
            'router_id' => $routerId,
            'mac_address' => $mac,
            'ip_address' => $ip,
            'voucher_id' => $this->id,
            'metadata' => [
                'created_via' => 'voucher_captive_portal',
                'voucher_code' => $this->code_display,
            ],
        ]);
    }

    public function toArrayForCaptivePortal(): array
    {
        return [
            'code' => $this->code_display,
            'prefix' => $this->prefix,
            'package' => $this->package ? $this->package->toArrayForCaptivePortal() : null,
            'validity' => $this->validity_window,
            'remaining_time' => $this->remaining_time,
            'remaining_time_formatted' => $this->remaining_time_formatted,
            'is_valid' => $this->is_valid_for_captive_portal,
            'max_redemptions' => $this->max_redemptions,
            'redemption_count' => $this->redemption_count,
            'redemptions_remaining' => $this->max_redemptions ? $this->max_redemptions - $this->redemption_count : null,
        ];
    }

    public function validateForCaptivePortal(string $phone): array
    {
        if (!$this->is_valid_for_captive_portal) {
            return $this->buildRedeemErrorResponse();
        }

        $existingSession = UserSession::findActiveByPhone($phone);
        
        if ($existingSession && $existingSession->package_id === $this->package_id) {
            return [
                'success' => false,
                'error' => 'active_session_exists',
                'message' => 'You already have an active session for this package',
                'existing_session' => $existingSession->toArrayForCaptivePortal(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Voucher is valid and ready to redeem',
            'voucher' => $this->toArrayForCaptivePortal(),
            'estimated_expiry' => $this->package?->getEstimatedExpiryForPhone($phone),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function markAsUsed(string $phone, string $mac, int $routerId): void
    {
        $this->update([
            'status' => self::STATUS_USED,
            'used_at' => now(),
            'used_by_phone' => $phone,
            'used_by_mac' => $mac,
            'router_id' => $routerId,
            'redeemed_via' => self::REDEEMED_VIA_ADMIN,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function markAsPrinted(): void
    {
        $this->update([
            'printed' => true,
            'printed_at' => now(),
        ]);
    }

    public function revoke(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['revoked_reason' => $reason, 'revoked_at' => now()->toIso8601String()]
            ),
        ]);

        Log::info('Voucher revoked', [
            'voucher_id' => $this->id,
            'code' => $this->code_display,
            'reason' => $reason,
        ]);
    }

    public function extendValidity(int $additionalHours): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        $this->update([
            'valid_until' => $this->valid_until->copy()->addHours($additionalHours),
            'validity_hours' => ($this->validity_hours ?? 0) + $additionalHours,
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['validity_extended_at' => now()->toIso8601String(), 'extended_by_hours' => $additionalHours]
            ),
        ]);

        return true;
    }

    public function canBeExtended(): bool
    {
        return $this->status === self::STATUS_UNUSED && !$this->is_expired;
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────

    public static function generateCode(int $length = 8, string $prefix = null): string
    {
        $characters = '0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    public static function findValidByCode(string $code): ?self
    {
        return static::byCode($code)
            ->forCaptivePortal()
            ->first();
    }

    public static function validateCodeForPortal(string $code, string $phone): array
    {
        $voucher = static::findValidByCode($code);
        
        if (!$voucher) {
            return [
                'valid' => false,
                'error' => 'invalid_code',
                'message' => 'Invalid or expired voucher code',
            ];
        }
        
        return $voucher->validateForCaptivePortal($phone);
    }

    public static function createForPackage(
        int $packageId,
        int $count = 1,
        array $options = []
    ): array {
        $vouchers = [];
        $batchId = $options['batch_id'] ?? Str::uuid()->toString();
        $validFrom = $options['valid_from'] ?? now();
        $validUntil = $options['valid_until'] ?? ($options['validity_hours'] ? now()->addHours($options['validity_hours']) : null);
        $captiveRedeemable = $options['captive_portal_redeemable'] ?? true;
        $maxRedemptions = $options['max_redemptions'] ?? null;
        $prefix = static::normalizePrefix($options['prefix'] ?? null);

        for ($i = 0; $i < $count; $i++) {
            $vouchers[] = static::create([
                'tenant_id' => $options['tenant_id'] ?? null,
                'package_id' => $packageId,
                'code' => static::generateCode($options['code_length'] ?? 8, $prefix),
                'prefix' => $prefix,
                'status' => self::STATUS_UNUSED,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'validity_hours' => $options['validity_hours'] ?? null,
                'batch_id' => $batchId,
                'batch_name' => $options['batch_name'],
                'captive_portal_redeemable' => $captiveRedeemable,
                'max_redemptions' => $maxRedemptions,
                'redemption_count' => 0,
                'metadata' => $options['metadata'] ?? [],
            ]);
        }

        return $vouchers;
    }

    public static function normalizePrefix(?string $prefix): ?string
    {
        $normalized = strtoupper(trim((string) $prefix));
        $normalized = preg_replace('/[^A-Z0-9-]/', '', $normalized);
        $normalized = trim((string) $normalized, '-');

        return $normalized !== '' ? $normalized : null;
    }

    public static function findActiveForPhone(string $phone, int $limit = 5)
    {
        return static::byPhone($phone)
            ->used()
            ->with('package')
            ->orderBy('used_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function cleanupExpiredVouchers(): int
    {
        $count = 0;
        
        static::query()
            ->where('status', self::STATUS_UNUSED)
            ->where('valid_until', '<', now())
            ->chunkById(100, function ($vouchers) use (&$count) {
                foreach ($vouchers as $voucher) {
                    $voucher->markAsExpired();
                    $count++;
                }
            });
        
        Log::info('Expired vouchers cleaned up', ['count' => $count]);
        
        return $count;
    }

    public static function getStatsForCaptivePortal(): array
    {
        return [
            'total_valid' => static::forCaptivePortal()->count(),
            'total_used_today' => static::used()
                ->whereDate('used_at', today())
                ->where('redeemed_via', self::REDEEMED_VIA_CAPTIVE_PORTAL)
                ->count(),
            'total_revenue_today' => static::used()
                ->whereDate('used_at', today())
                ->where('redeemed_via', self::REDEEMED_VIA_CAPTIVE_PORTAL)
                ->with('package')
                ->get()
                ->sum(function($v) {
                    return $v->package?->price ?? 0;
                }),
            'expiring_soon' => static::forCaptivePortal()
                ->where('valid_until', '<=', now()->addHours(24))
                ->count(),
        ];
    }
}
