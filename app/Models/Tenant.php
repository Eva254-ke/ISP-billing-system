<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // ──────────────────────────────────────────────────────────────────
        // BASIC TENANT INFO
        // ──────────────────────────────────────────────────────────────────
        'name',
        'subdomain',
        'domain',
        'contact_email',
        'contact_phone',
        'timezone',
        'currency',
        'status',
        
        // ──────────────────────────────────────────────────────────────────
        // BILLING & PLAN
        // ──────────────────────────────────────────────────────────────────
        'plan',
        'monthly_fee',
        'billing_cycle_start',
        'next_billing_date',
        'max_routers',
        'max_users',
        
        // ──────────────────────────────────────────────────────────────────
        // PAYMENT CONFIGURATION (Multi-Method Support)
        // ──────────────────────────────────────────────────────────────────
        'payment_method',           // paybill, till, personal, bank_eazzy
        'payment_shortcode',        // Paybill number (e.g., "247247")
        'till_number',              // Till number (if method = 'till')
        'payment_account_name',     // Account name for Paybill transactions
        'bank_account',             // Bank account (for Equity EazzyPay)
        'bank_code',                // Bank code (e.g., "EQTY")
        'personal_phone',           // Personal M-Pesa number (if method = 'personal')
        
        // ──────────────────────────────────────────────────────────────────
        // COMMISSION SETTINGS
        // ──────────────────────────────────────────────────────────────────
        'commission_type',          // percentage, fixed
        'commission_rate',          // 5.00 = 5% or KES 5.00
        'minimum_commission',       // Minimum commission amount
        'commission_frequency',     // monthly, weekly, per_transaction
        'next_commission_date',     // Next commission billing date
        
        // ──────────────────────────────────────────────────────────────────
        // CALLBACK & INTEGRATION
        // ──────────────────────────────────────────────────────────────────
        'custom_callback_url',      // Override default callback URL
        
        // ──────────────────────────────────────────────────────────────────
        // METADATA & SETTINGS
        // ──────────────────────────────────────────────────────────────────
        'settings',
        'trial_ends_at',
        'last_active_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'monthly_fee' => 'decimal:2',
        'billing_cycle_start' => 'date',
        'next_billing_date' => 'date',
        'trial_ends_at' => 'datetime',
        'last_active_at' => 'datetime',
        
        // Payment & Commission casts
        'commission_rate' => 'decimal:2',
        'minimum_commission' => 'decimal:2',
        'next_commission_date' => 'date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'bank_account',
        'personal_phone',
        'settings',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function paymentReconciliations(): HasMany
    {
        return $this->hasMany(PaymentReconciliation::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES (Common Queries)
    // ──────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('next_billing_date', '<=', now()->addDays($days));
    }

    public function scopeOverLimit($query)
    {
        return $query->whereHas('routers', function ($q) {
            $q->selectRaw('tenant_id, COUNT(*) as count')
                ->groupBy('tenant_id')
                ->havingRaw('count > tenants.max_routers');
        });
    }

    public function scopeWithPaymentConfigured($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('payment_shortcode')
              ->orWhereNotNull('till_number')
              ->orWhereNotNull('bank_account')
              ->orWhereNotNull('personal_phone');
        });
    }

    public function scopeNeedsCommissionBilling($query)
    {
        return $query->where('next_commission_date', '<=', now())
            ->where('status', 'active');
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS & ATTRIBUTES
    // ──────────────────────────────────────────────────────────────────────

    public function getFullDomainAttribute(): string
    {
        return $this->domain ?? "{$this->subdomain}.cloudbridge.network";
    }

    public function getIsOnTrialAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Get complete payment configuration as array
     */
    public function getPaymentConfigAttribute(): array
    {
        return [
            'method' => $this->payment_method,
            'shortcode' => $this->payment_shortcode,
            'till_number' => $this->till_number,
            'account_name' => $this->payment_account_name,
            'bank_account' => $this->bank_account,
            'bank_code' => $this->bank_code,
            'personal_phone' => $this->personal_phone,
            'is_configured' => $this->payment_shortcode || $this->till_number || $this->bank_account || $this->personal_phone,
        ];
    }

    /**
     * Get commission configuration as array
     */
    public function getCommissionConfigAttribute(): array
    {
        return [
            'type' => $this->commission_type,
            'rate' => $this->commission_rate,
            'minimum' => $this->minimum_commission,
            'frequency' => $this->commission_frequency,
            'next_billing' => $this->next_commission_date,
        ];
    }

    /**
     * Get the callback URL for this tenant (custom or default)
     */
    public function getCallbackUrlAttribute(): string
    {
        if ($this->custom_callback_url) {
            return $this->custom_callback_url;
        }
        
        // Default: route to our controller with tenant ID
        return route('api.mpesa.callback', ['tenant' => $this->id], false);
    }

    /**
     * Check if tenant has payment method configured
     */
    public function getHasPaymentMethodAttribute(): bool
    {
        return !empty($this->payment_shortcode) 
            || !empty($this->till_number) 
            || !empty($this->bank_account) 
            || !empty($this->personal_phone);
    }

    /**
     * Get payment method label for display
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'paybill' => 'Paybill',
            'till' => 'Till Number',
            'bank_eazzy' => 'Equity EazzyPay',
            'personal' => 'Personal M-Pesa',
            default => 'Not Configured',
        };
    }

    // ──────────────────────────────────────────────────────────────────────
    // COMMISSION CALCULATION
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Calculate commission for a given transaction amount
     */
    public function calculateCommission(float $amount): float
    {
        if ($this->commission_type === 'fixed') {
            return min($this->commission_rate, $amount); // Don't charge more than transaction
        }
        
        // Percentage-based
        $percentage = ($amount * $this->commission_rate) / 100;
        
        // Apply minimum
        return max($percentage, $this->minimum_commission);
    }

    /**
     * Get total commission owed by this tenant
     */
    public function getCommissionOwedAttribute(): float
    {
        // Sum of all completed payments since last commission billing
        $cutoff = $this->next_commission_date ? 
            \Carbon\Carbon::parse($this->next_commission_date) : 
            now()->subMonth();
        
        return $this->payments()
            ->where('status', 'completed')
            ->where('created_at', '>=', $cutoff)
            ->get()
            ->sum(fn($payment) => $this->calculateCommission($payment->amount));
    }

    /**
     * Get commission summary for display
     */
    public function getCommissionSummaryAttribute(): array
    {
        $owed = $this->commission_owed;
        $lastBilled = $this->paymentReconciliations()
            ->whereNotNull('resolved_at')
            ->latest('resolved_at')
            ->first();
        
        return [
            'owed' => $owed,
            'owed_formatted' => 'KES ' . number_format($owed, 2),
            'last_billed' => $lastBilled?->reconciliation_date,
            'next_billing' => $this->next_commission_date,
            'transactions_count' => $this->payments()
                ->where('status', 'completed')
                ->where('created_at', '>=', $this->next_commission_date ?? now()->subMonth())
                ->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // PAYMENT METHOD HELPERS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Check if tenant uses Safaricom Daraja API (Paybill/Till)
     */
    public function usesDaraja(): bool
    {
        return in_array($this->payment_method, ['paybill', 'till']);
    }

    /**
     * Check if tenant uses Equity EazzyPay
     */
    public function usesEquityEazzy(): bool
    {
        return $this->payment_method === 'bank_eazzy';
    }

    /**
     * Check if tenant uses Personal M-Pesa (SMS instructions only)
     */
    public function usesPersonalMpesa(): bool
    {
        return $this->payment_method === 'personal';
    }

    /**
     * Get the shortcode/till for API calls
     */
    public function getApiShortcodeAttribute(): ?string
    {
        return $this->payment_shortcode ?? $this->till_number;
    }

    /**
     * Get the account reference for STK Push
     */
    public function getApiAccountReferenceAttribute(): string
    {
        return $this->payment_account_name ?? $this->name ?? 'WiFi Payment';
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function canAddRouter(): bool
    {
        return $this->routers()->count() < $this->max_routers;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function getRevenueThisMonth(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
    }

    /**
     * Get total revenue for date range
     */
    public function getRevenueForPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    /**
     * Get active session count across all routers
     */
    public function getActiveSessionCountAttribute(): int
    {
        return $this->userSessions()
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get online router count
     */
    public function getOnlineRouterCountAttribute(): int
    {
        return $this->routers()
            ->where('status', 'online')
            ->count();
    }

    /**
     * Update next commission billing date
     */
    public function scheduleNextCommission(): void
    {
        $this->update([
            'next_commission_date' => match($this->commission_frequency) {
                'weekly' => now()->addWeek(),
                'per_transaction' => null, // Bill immediately after each transaction
                'monthly' => now()->addMonth(),
                default => now()->addMonth(),
            },
        ]);
    }

    /**
     * Mark commission as billed
     */
    public function markCommissionBilled(float $amount, string $reference = null): void
    {
        $this->update([
            'next_commission_date' => match($this->commission_frequency) {
                'weekly' => now()->addWeek(),
                'per_transaction' => now()->addDay(), // Next transaction
                'monthly' => now()->addMonth(),
                default => now()->addMonth(),
            },
        ]);

        // Log the billing event
        $this->auditLogs()->create([
            'event' => 'commission.billed',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'actor_type' => 'system',
            'actor_name' => 'Commission System',
            'metadata' => [
                'amount' => $amount,
                'reference' => $reference,
                'frequency' => $this->commission_frequency,
            ],
        ]);
    }

    /**
     * Validate payment configuration before going live
     */
    public function validatePaymentConfig(): array
    {
        $errors = [];
        
        if (!$this->payment_method) {
            $errors[] = 'Payment method is required';
        }
        
        if ($this->usesDaraja() && !$this->payment_shortcode) {
            $errors[] = 'Paybill number is required for Paybill/Till payments';
        }
        
        if ($this->payment_method === 'till' && !$this->till_number) {
            $errors[] = 'Till number is required for Till payments';
        }
        
        if ($this->usesEquityEazzy() && !$this->bank_account) {
            $errors[] = 'Bank account is required for Equity EazzyPay';
        }
        
        if ($this->usesPersonalMpesa() && !$this->personal_phone) {
            $errors[] = 'Personal M-Pesa number is required';
        }
        
        if ($this->commission_type === 'percentage' && (!$this->commission_rate || $this->commission_rate < 0)) {
            $errors[] = 'Valid commission rate is required';
        }
        
        if ($this->commission_type === 'fixed' && (!$this->commission_rate || $this->commission_rate < 0)) {
            $errors[] = 'Valid fixed commission amount is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}