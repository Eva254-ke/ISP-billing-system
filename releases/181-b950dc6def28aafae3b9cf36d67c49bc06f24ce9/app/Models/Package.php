<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $package): void {
            $package->code = static::generateUniqueCode(
                preferredCode: (string) ($package->code ?? ''),
                fallbackName: (string) ($package->name ?? '')
            );
        });
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'code',
        'price',
        'currency',
        'duration_value',
        'duration_unit',
        'download_limit_mbps',
        'upload_limit_mbps',
        'data_limit_mb',
        'mikrotik_profile_name',
        'mikrotik_pool_name',
        'is_active',
        'is_featured',
        'total_sales',
        'total_revenue',
        'sort_order',
        'captive_portal_visible',
        'captive_portal_priority',
        'allow_session_extension',
        'extension_price_multiplier',
        'grace_period_minutes',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_value' => 'integer',
        'download_limit_mbps' => 'integer',
        'upload_limit_mbps' => 'integer',
        'data_limit_mb' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'total_sales' => 'integer',
        'total_revenue' => 'decimal:2',
        'sort_order' => 'integer',
        'captive_portal_visible' => 'boolean',
        'captive_portal_priority' => 'integer',
        'allow_session_extension' => 'boolean',
        'extension_price_multiplier' => 'decimal:2',
        'grace_period_minutes' => 'integer',
        'metadata' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function scopeForCaptivePortal($query)
    {
        return $query->active()
            ->where('captive_portal_visible', true)
            ->orderBy('captive_portal_priority', 'asc')
            ->orderBy('price', 'asc');
    }

    public function scopeAllowExtension($query)
    {
        return $query->where('allow_session_extension', true);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getDurationInMinutesAttribute(): int
    {
        return match($this->duration_unit) {
            'minutes' => $this->duration_value,
            'hours' => $this->duration_value * 60,
            'days' => $this->duration_value * 1440,
            'weeks' => $this->duration_value * 10080,
            'months' => $this->duration_value * 43200,
            default => $this->duration_value,
        };
    }

    public function getDurationFormattedAttribute(): string
    {
        if ($this->duration_in_minutes >= 1440) {
            $days = $this->duration_in_minutes / 1440;
            return $days == 1 ? '1 day' : "{$days} days";
        }
        if ($this->duration_in_minutes >= 60) {
            $hours = $this->duration_in_minutes / 60;
            return $hours == 1 ? '1 hour' : "{$hours} hours";
        }
        return "{$this->duration_in_minutes} min";
    }

    public function getBandwidthFormattedAttribute(): string
    {
        $down = $this->download_limit_mbps ?? '∞';
        $up = $this->upload_limit_mbps ?? '∞';
        return "{$down}↓ / {$up}↑ Mbps";
    }

    public function getDataLimitFormattedAttribute(): ?string
    {
        if (!$this->data_limit_mb) {
            return 'Unlimited';
        }
        if ($this->data_limit_mb >= 1024) {
            $gb = $this->data_limit_mb / 1024;
            return $gb == 1 ? '1 GB' : "{$gb} GB";
        }
        return "{$this->data_limit_mb} MB";
    }

    public function getIsPopularAttribute(): bool
    {
        return $this->is_featured || ($this->captive_portal_priority <= 2 && $this->captive_portal_visible);
    }

    public function getExtensionPriceAttribute(): float
    {
        return round($this->price * ($this->extension_price_multiplier ?? 1), 2);
    }

    public function getGracePeriodSecondsAttribute(): int
    {
        return ($this->grace_period_minutes ?? 5) * 60;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function toArrayForCaptivePortal(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'price' => $this->price,
            'currency' => $this->currency,
            'extension_price' => $this->extension_price,
            'duration_value' => $this->duration_value,
            'duration_unit' => $this->duration_unit,
            'duration_minutes' => $this->duration_in_minutes,
            'duration_formatted' => $this->duration_formatted,
            'download_limit_mbps' => $this->download_limit_mbps,
            'upload_limit_mbps' => $this->upload_limit_mbps,
            'bandwidth_formatted' => $this->bandwidth_formatted,
            'data_limit_mb' => $this->data_limit_mb,
            'data_limit_formatted' => $this->data_limit_formatted,
            'mikrotik_profile_name' => $this->mikrotik_profile_name,
            'mikrotik_pool_name' => $this->mikrotik_pool_name,
            'is_popular' => $this->is_popular,
            'allow_session_extension' => $this->allow_session_extension,
            'grace_period_minutes' => $this->grace_period_minutes,
        ];
    }

    public function canPurchaseForPhone(string $phone): bool
    {
        $activeSession = UserSession::findActiveByPhone($phone);
        
        if ($activeSession && $activeSession->package_id === $this->id) {
            return false;
        }
        
        return true;
    }

    public function canExtendSession(string $phone): bool
    {
        if (!$this->allow_session_extension) {
            return false;
        }
        
        $activeSession = UserSession::findActiveByPhone($phone);
        return $activeSession && $activeSession->canReconnect();
    }

    public function getEstimatedExpiryForPhone(string $phone, bool $isExtension = false): ?string
    {
        $activeSession = UserSession::findActiveByPhone($phone);
        
        if ($isExtension && $activeSession && $activeSession->canReconnect()) {
            $newExpiry = $activeSession->expires_at->copy()->addMinutes($this->duration_in_minutes);
            return $newExpiry->toIso8601String();
        }
        
        if (!$isExtension && $activeSession && $activeSession->canReconnect()) {
            $newExpiry = $activeSession->expires_at->copy()->addMinutes($this->duration_in_minutes);
            return $newExpiry->toIso8601String();
        }
        
        return now()->copy()->addMinutes($this->duration_in_minutes)->toIso8601String();
    }

    public function getMikroTikRateLimit(): string
    {
        $download = $this->download_limit_mbps ?? '100M';
        $upload = $this->upload_limit_mbps ?? '100M';
        return "{$download}M/{$upload}M";
    }

    public function getMikroTikUserParams(string $username, string $password): array
    {
        $params = [
            'name' => $username,
            'password' => $password,
            'limit-uptime' => $this->duration_in_minutes . 'm',
        ];

        $profile = trim((string) ($this->mikrotik_profile_name ?? ''));
        if ($profile !== '') {
            $params['profile'] = $profile;
        }

        if ($this->data_limit_mb) {
            $params['limit-bytes-total'] = (string)($this->data_limit_mb * 1024 * 1024);
        }

        if ($this->download_limit_mbps || $this->upload_limit_mbps) {
            $params['rate-limit'] = $this->getMikroTikRateLimit();
        }

        return $params;
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function incrementSales(float $amount): void
    {
        $this->increment('total_sales');
        $this->increment('total_revenue', $amount);
    }

    public function recordCaptivePurchase(string $phone, string $reference): void
    {
        $this->incrementSales($this->price);
        
        Log::info('Package purchased via captive portal', [
            'package_id' => $this->id,
            'package_name' => $this->name,
            'phone' => $phone,
            'reference' => $reference,
            'amount' => $this->price,
        ]);
    }

    public function validateForCaptivePortal(): array
    {
        $errors = [];

        if (!$this->is_active) {
            $errors[] = 'Package is not active';
        }

        if (!$this->captive_portal_visible) {
            $errors[] = 'Package not visible in captive portal';
        }

        if ($this->price <= 0) {
            $errors[] = 'Invalid package price';
        }

        if ($this->duration_in_minutes <= 0) {
            $errors[] = 'Invalid package duration';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────

    public static function findForCaptivePortalById(int $id): ?self
    {
        return static::forCaptivePortal()->find($id);
    }

    public static function getPopularForCaptivePortal(int $limit = 3)
    {
        return static::forCaptivePortal()
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }

    public static function findBestValueForCaptivePortal(): ?self
    {
        return static::forCaptivePortal()
            ->get()
            ->sortByDesc(function ($p) {
                return $p->duration_in_minutes / max($p->price, 1);
            })
            ->first();
    }

    public static function generateUniqueCode(?string $preferredCode = null, ?string $fallbackName = null): string
    {
        $base = static::normalizeCodeBase($preferredCode);

        if ($base === '') {
            $base = static::normalizeCodeBase($fallbackName);
        }

        if ($base === '') {
            $base = 'PKG';
        }

        if (!static::query()->where('code', $base)->exists()) {
            return $base;
        }

        do {
            $candidate = $base . '-' . Str::upper(Str::random(4));
        } while (static::query()->where('code', $candidate)->exists());

        return $candidate;
    }

    private static function normalizeCodeBase(?string $value): string
    {
        $normalized = strtoupper((string) $value);
        $normalized = preg_replace('/[^A-Z0-9]+/', '-', $normalized) ?? '';

        return trim(substr($normalized, 0, 48), '-');
    }
}
