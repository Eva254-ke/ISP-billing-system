<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

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
        'logo_url',
        'brand_color_primary',
        'brand_color_secondary',
        
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
        'payment_method',
        'payment_shortcode',
        'till_number',
        'payment_account_name',
        'bank_account',
        'bank_code',
        'personal_phone',
        'intasend_public_key',
        'intasend_secret_key',
        'intasend_mode',
        
        // ──────────────────────────────────────────────────────────────────
        // COMMISSION SETTINGS
        // ──────────────────────────────────────────────────────────────────
        'commission_type',
        'commission_rate',
        'minimum_commission',
        'commission_frequency',
        'next_commission_date',
        
        // ──────────────────────────────────────────────────────────────────
        // CALLBACK & INTEGRATION
        // ──────────────────────────────────────────────────────────────────
        'custom_callback_url',
        
        // ──────────────────────────────────────────────────────────────────
        // CAPTIVE PORTAL SETTINGS
        // ──────────────────────────────────────────────────────────────────
        'captive_portal_enabled',
        'captive_portal_title',
        'captive_portal_welcome_message',
        'captive_portal_terms_url',
        'captive_portal_support_phone',
        'captive_portal_support_email',
        'captive_portal_custom_css',
        'captive_portal_redirect_url',
        'captive_portal_session_timeout_minutes',
        'captive_portal_grace_period_minutes',
        'captive_portal_allow_voucher_redemption',
        'captive_portal_allow_mpese_code_reconnect',
        'captive_portal_show_package_descriptions',
        'captive_portal_default_language',
        'captive_portal_analytics_enabled',
        
        // ──────────────────────────────────────────────────────────────────
        // METADATA & SETTINGS
        // ──────────────────────────────────────────────────────────────────
        'settings',
        'trial_ends_at',
        'last_active_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'monthly_fee' => 'decimal:2',
        'billing_cycle_start' => 'date',
        'next_billing_date' => 'date',
        'trial_ends_at' => 'datetime',
        'last_active_at' => 'datetime',
        
        // Payment & Commission casts
        'commission_rate' => 'decimal:2',
        'minimum_commission' => 'decimal:2',
        'next_commission_date' => 'date',
        
        // Captive portal casts
        'captive_portal_enabled' => 'boolean',
        'captive_portal_session_timeout_minutes' => 'integer',
        'captive_portal_grace_period_minutes' => 'integer',
        'captive_portal_allow_voucher_redemption' => 'boolean',
        'captive_portal_allow_mpese_code_reconnect' => 'boolean',
        'captive_portal_show_package_descriptions' => 'boolean',
        'captive_portal_analytics_enabled' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'bank_account',
        'personal_phone',
        'intasend_secret_key',
        'settings',
        'metadata',
    ];

    /**
     * The attributes that should be appended to arrays.
     */
    protected $appends = [
        'full_domain',
        'is_on_trial',
        'payment_config',
        'commission_config',
        'callback_url',
        'has_payment_method',
        'payment_method_label',
        'captive_portal_config',
        'captive_portal_url',
        'brand_colors',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────────────

    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_TRIAL = 'trial';
    const STATUS_EXPIRED = 'expired';

    const PLAN_FREE = 'free';
    const PLAN_BASIC = 'basic';
    const PLAN_PRO = 'pro';
    const PLAN_ENTERPRISE = 'enterprise';

    const PAYMENT_METHOD_PAYBILL = 'paybill';
    const PAYMENT_METHOD_TILL = 'till';
    const PAYMENT_METHOD_BANK_EAZZY = 'bank_eazzy';
    const PAYMENT_METHOD_PERSONAL = 'personal';

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
        return $query->where('status', self::STATUS_ACTIVE);
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

    public function scopeWithCaptivePortal($query)
    {
        return $query->active()
            ->where('captive_portal_enabled', true);
    }

    public function scopeBySubdomain($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where(function($q) use ($domain) {
            $q->where('domain', $domain)
              ->orWhere('subdomain', $domain);
        });
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
            'intasend_public_key' => $this->intasend_public_key,
            'intasend_mode' => $this->intasend_mode,
            'is_configured' => $this->payment_shortcode || $this->till_number || $this->bank_account || $this->personal_phone,
        ];
    }

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

    public function getCallbackUrlAttribute(): string
    {
        if ($this->custom_callback_url) {
            return $this->custom_callback_url;
        }

        if (Route::has('api.mpesa.callback')) {
            return route('api.mpesa.callback', ['tenant' => $this->id], false);
        }

        if (Route::has('api.mpesa.callback.legacy')) {
            return route('api.mpesa.callback.legacy', [], false);
        }

        return '/api/mpesa/callback/' . $this->id;
    }

    public function getHasPaymentMethodAttribute(): bool
    {
        return !empty($this->payment_shortcode) 
            || !empty($this->till_number) 
            || !empty($this->bank_account) 
            || !empty($this->personal_phone);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::PAYMENT_METHOD_PAYBILL => 'Paybill',
            self::PAYMENT_METHOD_TILL => 'Till Number',
            self::PAYMENT_METHOD_BANK_EAZZY => 'Equity EazzyPay',
            self::PAYMENT_METHOD_PERSONAL => 'Personal M-Pesa',
            default => 'Not Configured',
        };
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getCaptivePortalConfigAttribute(): array
    {
        return [
            'enabled' => $this->captive_portal_enabled,
            'title' => $this->captive_portal_title ?? $this->name . ' WiFi',
            'welcome_message' => $this->captive_portal_welcome_message,
            'terms_url' => $this->captive_portal_terms_url,
            'support_phone' => $this->captive_portal_support_phone,
            'support_email' => $this->captive_portal_support_email,
            'custom_css' => $this->captive_portal_custom_css,
            'redirect_url' => $this->captive_portal_redirect_url ?? 'http://google.com',
            'session_timeout_minutes' => $this->captive_portal_session_timeout_minutes ?? 60,
            'grace_period_minutes' => $this->captive_portal_grace_period_minutes ?? 5,
            'allow_voucher_redemption' => $this->captive_portal_allow_voucher_redemption ?? true,
            'allow_mpese_code_reconnect' => $this->captive_portal_allow_mpese_code_reconnect ?? true,
            'show_package_descriptions' => $this->captive_portal_show_package_descriptions ?? true,
            'default_language' => $this->captive_portal_default_language ?? 'en',
            'analytics_enabled' => $this->captive_portal_analytics_enabled ?? false,
            'brand_colors' => $this->brand_colors,
        ];
    }

    public function getCaptivePortalUrlAttribute(): string
    {
        if ($this->domain) {
            return "https://{$this->domain}/wifi";
        }
        return "https://{$this->subdomain}.cloudbridge.network/wifi";
    }

    public function getBrandColorsAttribute(): array
    {
        return [
            'primary' => $this->brand_color_primary ?? '#7C3AED',
            'secondary' => $this->brand_color_secondary ?? '#06B6D4',
        ];
    }

    public function getCaptivePortalThemeAttribute(): array
    {
        return [
            'colors' => $this->brand_colors,
            'custom_css' => $this->captive_portal_custom_css,
            'logo_url' => $this->logo_url,
            'title' => $this->captive_portal_title ?? $this->name . ' WiFi',
            'welcome_message' => $this->captive_portal_welcome_message,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // COMMISSION CALCULATION
    // ──────────────────────────────────────────────────────────────────────

    public function calculateCommission(float $amount): float
    {
        if ($this->commission_type === 'fixed') {
            return min($this->commission_rate, $amount);
        }
        $percentage = ($amount * $this->commission_rate) / 100;
        return max($percentage, $this->minimum_commission ?? 0);
    }

    public function getCommissionOwedAttribute(): float
    {
        $cutoff = $this->next_commission_date ? 
            \Carbon\Carbon::parse($this->next_commission_date) : 
            now()->subMonth();
        
        return $this->payments()
            ->where('status', 'completed')
            ->where('created_at', '>=', $cutoff)
            ->get()
            ->sum(fn($payment) => $this->calculateCommission($payment->amount));
    }

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

    public function usesDaraja(): bool
    {
        return in_array($this->payment_method, [self::PAYMENT_METHOD_PAYBILL, self::PAYMENT_METHOD_TILL]);
    }

    public function usesEquityEazzy(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BANK_EAZZY;
    }

    public function usesPersonalMpesa(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PERSONAL;
    }

    public function usesIntaSend(): bool
    {
        return !empty($this->intasend_public_key) && !empty($this->intasend_secret_key);
    }

    public function getApiShortcodeAttribute(): ?string
    {
        return $this->payment_shortcode ?? $this->till_number;
    }

    public function getApiAccountReferenceAttribute(): string
    {
        return $this->payment_account_name ?? $this->name ?? 'WiFi Payment';
    }

    public function getIntaSendConfigAttribute(): ?array
    {
        if (!$this->usesIntaSend()) {
            return null;
        }
        
        return [
            'public_key' => $this->intasend_public_key,
            'secret_key' => $this->intasend_secret_key,
            'mode' => $this->intasend_mode ?? 'sandbox',
            'callback_url' => $this->callback_url,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // CAPTIVE PORTAL METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function isCaptivePortalEnabled(): bool
    {
        return $this->captive_portal_enabled && $this->status === self::STATUS_ACTIVE;
    }

    public function getActivePackagesForCaptivePortal()
    {
        return $this->packages()
            ->active()
            ->forCaptivePortal()
            ->get();
    }

    public function getValidVouchersForCaptivePortal()
    {
        if (!$this->captive_portal_allow_voucher_redemption) {
            return collect();
        }
        
        return $this->vouchers()
            ->forCaptivePortal()
            ->get();
    }

    public function findRouterForCaptivePortal(?string $phone = null, ?string $mac = null): ?Router
    {
        $query = $this->routers()
            ->withCaptivePortal()
            ->healthy();
        
        if ($mac) {
            $query->whereHas('userSessions', function($q) use ($mac) {
                $q->where('mac_address', $mac);
            });
        }
        
        return $query->orderBy('active_sessions', 'asc')->first();
    }

    public function getCaptivePortalAnalytics(): array
    {
        if (!$this->captive_portal_analytics_enabled) {
            return [];
        }
        
        $today = now()->startOfDay();
        
        return [
            'sessions_today' => $this->userSessions()
                ->where('created_at', '>=', $today)
                ->count(),
            'revenue_today' => $this->payments()
                ->captivePortal()
                ->successful()
                ->where('created_at', '>=', $today)
                ->sum('amount'),
            'active_sessions' => $this->userSessions()
                ->active()
                ->count(),
            'vouchers_redeemed_today' => $this->vouchers()
                ->used()
                ->where('redeemed_via', 'captive_portal')
                ->whereDate('used_at', today())
                ->count(),
            'top_package' => $this->payments()
                ->captivePortal()
                ->successful()
                ->where('created_at', '>=', $today)
                ->selectRaw('package_id, COUNT(*) as count')
                ->groupBy('package_id')
                ->orderByDesc('count')
                ->first()?->package?->name,
        ];
    }

    public function validateCaptivePortalConfig(): array
    {
        $errors = [];
        
        if ($this->captive_portal_enabled) {
            if (!$this->has_payment_method) {
                $errors[] = 'Payment method must be configured for captive portal';
            }
            
            if (!$this->packages()->active()->forCaptivePortal()->exists()) {
                $errors[] = 'At least one active package must be visible in captive portal';
            }
            
            if ($this->captive_portal_allow_voucher_redemption) {
                if (!$this->vouchers()->valid()->exists()) {
                    Log::warning('Voucher redemption enabled but no valid vouchers exist', [
                        'tenant_id' => $this->id,
                    ]);
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $this->captive_portal_enabled ? $this->getCaptivePortalWarnings() : [],
        ];
    }

    protected function getCaptivePortalWarnings(): array
    {
        $warnings = [];
        
        if (!$this->captive_portal_support_phone && !$this->captive_portal_support_email) {
            $warnings[] = 'No support contact configured for captive portal';
        }
        
        if (!$this->captive_portal_terms_url) {
            $warnings[] = 'Terms of service URL not configured';
        }
        
        if ($this->captive_portal_session_timeout_minutes < 5) {
            $warnings[] = 'Session timeout is very short (< 5 minutes)';
        }
        
        return $warnings;
    }

    public function syncCaptivePortalSettingsToRouters(): int
    {
        $count = 0;
        
        $this->routers()->withCaptivePortal()->chunk(10, function($routers) use (&$count) {
            foreach ($routers as $router) {
                if ($router->syncWalledGarden()) {
                    $count++;
                }
            }
        });
        
        Log::info('Captive portal settings synced to routers', [
            'tenant_id' => $this->id,
            'routers_updated' => $count,
        ]);
        
        return $count;
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

    public function getRevenueForPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    public function getActiveSessionCountAttribute(): int
    {
        return $this->userSessions()
            ->where('status', 'active')
            ->count();
    }

    public function getOnlineRouterCountAttribute(): int
    {
        return $this->routers()
            ->where('status', 'online')
            ->count();
    }

    public function scheduleNextCommission(): void
    {
        $this->update([
            'next_commission_date' => match($this->commission_frequency) {
                'weekly' => now()->addWeek(),
                'per_transaction' => null,
                'monthly' => now()->addMonth(),
                default => now()->addMonth(),
            },
        ]);
    }

    public function markCommissionBilled(float $amount, ?string $reference = null): void
    {
        $this->update([
            'next_commission_date' => match($this->commission_frequency) {
                'weekly' => now()->addWeek(),
                'per_transaction' => now()->addDay(),
                'monthly' => now()->addMonth(),
                default => now()->addMonth(),
            },
        ]);

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

    public function validatePaymentConfig(): array
    {
        $errors = [];
        
        if (!$this->payment_method) {
            $errors[] = 'Payment method is required';
        }
        
        if ($this->payment_method === self::PAYMENT_METHOD_PAYBILL && !$this->payment_shortcode) {
            $errors[] = 'Paybill number is required for Paybill payments';
        }
        
        if ($this->payment_method === self::PAYMENT_METHOD_TILL && !$this->till_number) {
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

    // ──────────────────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────────────────

    public static function findBySubdomainOrDomain(string $identifier): ?self
    {
        $cacheKey = "tenant_by_{$identifier}";

        $cached = Cache::get($cacheKey);
        if (is_object($cached)) {
            // Drop stale serialized objects from old cache entries.
            Cache::forget($cacheKey);
            $cached = null;
        }

        $tenantId = is_numeric($cached) ? (int) $cached : 0;
        if ($tenantId <= 0) {
            $tenantId = (int) static::query()
                ->where('subdomain', $identifier)
                ->orWhere('domain', $identifier)
                ->active()
                ->value('id');

            Cache::put($cacheKey, $tenantId ?: null, 300);
        }

        if ($tenantId <= 0) {
            return null;
        }

        return static::active()->find($tenantId);
    }

    public static function findWithCaptivePortalById(int $id): ?self
    {
        return static::withCaptivePortal()->find($id);
    }

    public static function getStatsForDashboard(): array
    {
        return [
            'total_tenants' => static::count(),
            'active_tenants' => static::active()->count(),
            'on_trial' => static::onTrial()->count(),
            'with_captive_portal' => static::withCaptivePortal()->count(),
            'revenue_this_month' => static::active()
                ->with('payments')
                ->get()
                ->sum(fn($t) => $t->getRevenueThisMonth()),
        ];
    }

    public static function cleanupExpiredTrials(): int
    {
        $count = 0;
        
        static::query()
            ->onTrial()
            ->where('trial_ends_at', '<', now())
            ->chunkById(100, function ($tenants) use (&$count) {
                foreach ($tenants as $tenant) {
                    $tenant->update([
                        'status' => self::STATUS_EXPIRED,
                        'captive_portal_enabled' => false,
                        'metadata' => array_merge(
                            $tenant->metadata ?? [],
                            ['trial_expired_at' => now()->toIso8601String()]
                        ),
                    ]);
                    $count++;
                }
            });
        
        Log::info('Expired trials cleaned up', ['count' => $count]);
        return $count;
    }

    // ──────────────────────────────────────────────────────────────────────
    // OUTPUT FOR CAPTIVE PORTAL
    // ──────────────────────────────────────────────────────────────────────

    public function toArrayForCaptivePortal(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency ?? 'KES',
            'timezone' => $this->timezone ?? 'Africa/Nairobi',
            'captive_portal' => $this->captive_portal_config,
            'theme' => $this->captive_portal_theme,
            'support' => [
                'phone' => $this->captive_portal_support_phone,
                'email' => $this->captive_portal_support_email,
                'terms_url' => $this->captive_portal_terms_url,
            ],
            'payment' => [
                'method' => $this->payment_method_label,
                'shortcode' => $this->api_shortcode,
                'account_name' => $this->api_account_reference,
            ],
            'analytics' => $this->captive_portal_analytics_enabled ? $this->getCaptivePortalAnalytics() : null,
        ];
    }
}
