<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'phone',
        'customer_name',
        'package_id',
        'package_name',
        'amount',
        'currency',
        'mpesa_checkout_request_id',
        'mpesa_receipt_number',
        'mpesa_transaction_id',
        'mpesa_phone',
        'mpesa_code',
        'status',
        'type',
        'reference',
        'reconnect_count',
        'parent_payment_id',
        'callback_data',
        'callback_payload',
        'callback_attempts',
        'initiated_at',
        'confirmed_at',
        'completed_at',
        'activated_at',
        'failed_at',
        'session_id',
        'reconciled',
        'reconciled_at',
        'reconciliation_notes',
        'payment_channel',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_data' => 'array',
        'callback_payload' => 'array',
        'callback_attempts' => 'integer',
        'reconnect_count' => 'integer',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'activated_at' => 'datetime',
        'failed_at' => 'datetime',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────────────

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ACTIVATED = 'activated';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    const TYPE_ADMIN = 'admin';
    const TYPE_CAPTIVE_PORTAL = 'captive_portal';
    const TYPE_SESSION_EXTENSION = 'session_extension';
    const TYPE_VOUCHER = 'voucher';

    const CHANNEL_MPESA = 'mpesa';
    const CHANNEL_CAPTIVE_PORTAL = 'captive_portal';
    const CHANNEL_SESSION_EXTENSION = 'session_extension';
    const CHANNEL_VOUCHER = 'voucher';
    const CHANNEL_CASH = 'cash';

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

    public function session(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'session_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    public function childPayments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeActivated($query)
    {
        return $query->where('status', self::STATUS_ACTIVATED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('reconciled', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeCaptivePortal($query)
    {
        return $query->where(function ($inner) {
            $inner->where('type', self::TYPE_CAPTIVE_PORTAL)
                ->orWhere('payment_channel', self::CHANNEL_CAPTIVE_PORTAL);
        });
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeRecent($query, $minutes = 30)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopePendingActivation($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->whereNull('activated_at');
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_ACTIVATED
        ]);
    }

    public function scopeForReconnection($query, $phone)
    {
        return $query->byPhone($phone)
            ->successful()
            ->where('type', self::TYPE_CAPTIVE_PORTAL)
            ->orderBy('created_at', 'desc');
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getIsReconcilableAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_ACTIVATED,
            self::STATUS_FAILED,
            self::STATUS_REFUNDED
        ]);
    }

    public function getIsCaptivePortalAttribute(): bool
    {
        return $this->type === self::TYPE_CAPTIVE_PORTAL
            || $this->payment_channel === self::CHANNEL_CAPTIVE_PORTAL;
    }

    public function getIsExtensionAttribute(): bool
    {
        return $this->type === self::TYPE_SESSION_EXTENSION
            || $this->payment_channel === self::CHANNEL_SESSION_EXTENSION;
    }

    public function getCanBeReconnectedAttribute(): bool
    {
        if (!in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ACTIVATED])) {
            return false;
        }

        if (!$this->package) {
            return false;
        }

        $expiry = $this->activated_at ?? $this->completed_at ?? $this->created_at;
        return $expiry->copy()->addMinutes($this->package->duration_in_minutes)->isFuture();
    }

    public function getMpesaCodeDisplayAttribute(): ?string
    {
        if (!$this->mpesa_code) {
            return null;
        }
        return strtoupper($this->mpesa_code);
    }

    public function getDisplayCustomerNameAttribute(): string
    {
        $directName = $this->normalizeCustomerName($this->customer_name);
        if ($directName !== null) {
            return $directName;
        }

        $candidateSources = array_filter([
            is_array($this->metadata) ? $this->metadata : null,
            is_array($this->callback_payload) ? $this->callback_payload : null,
            is_array($this->callback_data) ? $this->callback_data : null,
        ]);

        $paths = [
            'customer_name',
            'customerName',
            'CustomerName',
            'payer_name',
            'payerName',
            'PayerName',
            'customer.name',
            'payer.name',
            'customer.full_name',
            'payer.full_name',
            'client_context.customer_name',
            'client_context.name',
            'client_context.full_name',
            'client_context.payer_name',
            'client.customer_name',
            'client.customerName',
            'name',
            'full_name',
            'FullName',
        ];

        foreach ($candidateSources as $source) {
            foreach ($paths as $path) {
                $resolved = $this->normalizeCustomerName((string) data_get($source, $path, ''));
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            $combinedName = $this->normalizeCustomerName(implode(' ', array_filter([
                (string) data_get($source, 'first_name', ''),
                (string) data_get($source, 'middle_name', ''),
                (string) data_get($source, 'last_name', ''),
                (string) data_get($source, 'FirstName', ''),
                (string) data_get($source, 'MiddleName', ''),
                (string) data_get($source, 'LastName', ''),
            ])));

            if ($combinedName !== null) {
                return $combinedName;
            }
        }

        $phoneLabel = $this->normalizeCustomerName($this->phone ?: $this->mpesa_phone);
        if ($phoneLabel !== null) {
            return $phoneLabel;
        }

        $sessionUsername = $this->normalizeCustomerName($this->session?->username);
        if ($sessionUsername !== null) {
            return $sessionUsername;
        }

        return 'Not captured';
    }

    public function getExpiryTimeAttribute(): ?string
    {
        if (!$this->package) {
            return null;
        }

        $baseTime = $this->activated_at ?? $this->completed_at ?? $this->created_at;
        return $baseTime->copy()->addMinutes($this->package->duration_in_minutes)->toIso8601String();
    }

    private function normalizeCustomerName(?string $value): ?string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function canActivateSession(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CONFIRMED])
            && !$this->activated_at
            && $this->package;
    }

    public function activateSession(array $sessionData = []): bool
    {
        if (!$this->canActivateSession()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACTIVATED,
            'activated_at' => now(),
        ]);

        if (!$this->session && $this->package) {
            UserSession::createFromPayment($this, $sessionData);
        }

        Log::info('Payment session activated', [
            'payment_id' => $this->id,
            'phone' => $this->phone,
            'reference' => $this->reference,
            'package' => $this->package?->name,
        ]);

        return true;
    }

    public function recordCallback(array $payload): void
    {
        $updates = [
            'callback_payload' => $payload,
            'callback_data' => array_merge($this->callback_data ?? [], $payload),
        ];

        if (isset($payload['transaction_id']) && !$this->mpesa_transaction_id) {
            $updates['mpesa_transaction_id'] = $payload['transaction_id'];
        }

        if (isset($payload['mpesa_code']) && !$this->mpesa_code) {
            $updates['mpesa_code'] = strtoupper($payload['mpesa_code']);
        }

        if (isset($payload['receipt_number']) && !$this->mpesa_receipt_number) {
            $updates['mpesa_receipt_number'] = $payload['receipt_number'];
        }

        $this->update($updates);

        Log::info('Payment callback recorded', [
            'payment_id' => $this->id,
            'reference' => $this->reference,
            'phone' => $this->phone,
            'status' => $payload['status'] ?? 'unknown',
        ]);
    }

    public function markFailed(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'reconciliation_notes' => $reason,
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['failure_reason' => $reason, 'failed_at' => now()->toIso8601String()]
            ),
        ]);

        Log::warning('Payment marked failed', [
            'payment_id' => $this->id,
            'phone' => $this->phone,
            'reference' => $this->reference,
            'reason' => $reason,
        ]);
    }

    public function toArrayForCaptivePortal(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'phone' => $this->phone,
            'customer_name' => $this->customer_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'type' => $this->type,
            'is_captive_portal' => $this->is_captive_portal,
            'can_be_reconnected' => $this->can_be_reconnected,
            'mpesa_code' => $this->mpesa_code_display,
            'mpesa_receipt' => $this->mpesa_receipt_number,
            'reconnect_count' => $this->reconnect_count,
            'created_at' => $this->created_at->toIso8601String(),
            'initiated_at' => $this->initiated_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'expires_at' => $this->expiry_time,
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'duration_minutes' => $this->package->duration_in_minutes,
                'duration_formatted' => $this->package->duration_formatted,
                'price' => $this->package->price,
                'data_limit_mb' => $this->package->data_limit_mb,
                'speed_mbps' => $this->package->speed_mbps,
            ] : null,
            'active_session' => $this->session?->isActive()
                ? $this->session->toArrayForCaptivePortal()
                : null,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS (STATE MACHINE)
    // ──────────────────────────────────────────────────────────────────────

    public function markPending(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'initiated_at' => $this->initiated_at ?? now(),
        ]);
    }

    public function markConfirmed(array $callbackData): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'callback_data' => array_merge($this->callback_data ?? [], $callbackData),
        ]);
    }

    public function markCompleted(UserSession $session): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'session_id' => $session->id,
        ]);
    }

    public function markReconciled(): void
    {
        $this->update([
            'reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }

    public function incrementCallbackAttempts(): void
    {
        $this->increment('callback_attempts');
    }

    public function recordReconnect(string $method = 'manual'): void
    {
        $this->increment('reconnect_count');
        $this->update([
            'metadata' => array_merge(
                $this->metadata ?? [],
                [
                    'last_reconnect_method' => $method,
                    'last_reconnect_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        Log::info('Payment reconnected', [
            'payment_id' => $this->id,
            'phone' => $this->phone,
            'method' => $method,
            'reconnect_count' => $this->reconnect_count,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────

    public static function findPendingByPhone(string $phone): ?self
    {
        return static::byPhone($phone)
            ->captivePortal()
            ->where('status', self::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public static function findLatestByPhone(string $phone): ?self
    {
        return static::byPhone($phone)
            ->captivePortal()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public static function findForReconnection(string $phone): ?self
    {
        return static::forReconnection($phone)->first();
    }

    public static function createCaptivePayment(
        string $phone,
        int $packageId,
        float $amount,
        string $reference,
        array $extra = []
    ): self {
        $package = Package::findOrFail($packageId);

        return static::create(array_merge([
            'phone' => $phone,
            'package_id' => $packageId,
            'package_name' => $package->name,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => self::STATUS_PENDING,
            'type' => self::TYPE_CAPTIVE_PORTAL,
            'reference' => $reference,
            'payment_channel' => self::CHANNEL_CAPTIVE_PORTAL,
            'metadata' => ['created_via' => 'captive_portal'],
        ], $extra));
    }

    public static function createExtensionPayment(
        string $phone,
        int $packageId,
        float $amount,
        string $reference,
        int $parentPaymentId,
        array $extra = []
    ): self {
        $package = Package::findOrFail($packageId);

        return static::create(array_merge([
            'phone' => $phone,
            'package_id' => $packageId,
            'package_name' => $package->name,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => self::STATUS_PENDING,
            'type' => self::TYPE_SESSION_EXTENSION,
            'reference' => $reference,
            'parent_payment_id' => $parentPaymentId,
            'payment_channel' => self::CHANNEL_SESSION_EXTENSION,
            'metadata' => ['created_via' => 'session_extension'],
        ], $extra));
    }
}
