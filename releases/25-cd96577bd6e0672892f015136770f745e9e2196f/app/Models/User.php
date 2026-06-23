<?php

namespace App\Models;

use App\Utils\IpAddressHelper;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable([
    'tenant_id',
    'name',
    'email',
    'password',
    'role',
    'permissions',
    'phone',
    'timezone',
    'is_active',
    'last_login_at',
    'last_login_ip',
    'password_changed_at',
])]
#[Hidden(['password', 'remember_token', 'email_verified_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    // ──────────────────────────────────────────────────────────────────────
    // CASTS (Modern Laravel 11+ Syntax)
    // ──────────────────────────────────────────────────────────────────────
    
    #[Cast('datetime')]
    public function emailVerifiedAt(): ?string
    {
        return $this->attributes['email_verified_at'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES (Query Helpers)
    // ──────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeAdmin($query)
    {
        return $query->whereIn('role', ['super_admin', 'tenant_admin']);
    }

    public function scopeOperator($query)
    {
        return $query->where('role', 'operator');
    }

    public function scopeViewer($query)
    {
        return $query->where('role', 'viewer');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS & ATTRIBUTES
    // ──────────────────────────────────────────────────────────────────────

    public function getIsAdminAttribute(): bool
    {
        return in_array($this->role, ['super_admin', 'tenant_admin'], true);
    }

    public function getIsOperatorAttribute(): bool
    {
        return $this->role === 'operator';
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name}");
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Admin',
            'tenant_admin' => 'ISP Admin',
            'operator' => 'Operator',
            'viewer' => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    // ──────────────────────────────────────────────────────────────────────
    // PERMISSIONS & AUTHORIZATION
    // ──────────────────────────────────────────────────────────────────────

    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->role === 'super_admin') {
            return true;
        }

        // Check permissions array
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        $userPermissions = $this->permissions ?? [];
        return count(array_intersect($permissions, $userPermissions)) > 0;
    }

    public function canAccessTenant(Tenant $tenant): bool
    {
        // Super admin can access all tenants
        if ($this->role === 'super_admin') {
            return true;
        }

        // Tenant admins and below can only access their own tenant
        return $this->tenant_id === $tenant->id;
    }

    public function canManageRouter(Router $router): bool
    {
        // Super admin can manage all routers
        if ($this->role === 'super_admin') {
            return true;
        }

        // Must belong to same tenant
        if ($this->tenant_id !== $router->tenant_id) {
            return false;
        }

        // Must have permission or be admin
        return $this->isAdmin || $this->hasPermission('routers.manage');
    }

    // ──────────────────────────────────────────────────────────────────────
    // AUTHENTICATION & SECURITY (Production-Ready)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Record login with REAL IP detection (not VPN/Proxy)
     */
    public function recordLogin(Request $request): void
    {
        // Get REAL client IP (handles VPN, Cloudflare, proxies)
        $realIp = IpAddressHelper::getClientIp($request);
        
        // Get browser fingerprint for session validation
        $fingerprint = IpAddressHelper::getBrowserFingerprint($request);
        
        // Get IP metadata (Geo, ISP, VPN detection)
        $ipMetadata = IpAddressHelper::getIpMetadata($realIp);

        // Update user record
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $realIp,
        ]);

        // Create comprehensive audit log
        AuditLog::create([
            'tenant_id' => $this->tenant_id,
            'event' => 'user.login',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'actor_type' => 'user',
            'actor_id' => $this->id,
            'actor_name' => $this->name,
            'ip_address' => $realIp,
            'user_agent' => $request->userAgent(),
            'metadata' => [
                // IP Information
                'real_ip' => $realIp,
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'is_vpn' => $ipMetadata['is_vpn'] ?? false,
                'country' => $ipMetadata['country'] ?? null,
                'city' => $ipMetadata['city'] ?? null,
                'isp' => $ipMetadata['isp'] ?? null,
                
                // Device/Browser Information
                'browser_fingerprint' => $fingerprint,
                'user_agent' => $request->userAgent(),
                'device_type' => $this->getDeviceType($request),
                'platform' => $this->getPlatform($request),
                
                // Request Context
                'url' => $request->fullUrl(),
                'referrer' => $request->header('referer'),
                'locale' => $request->getPreferredLanguage(),
            ],
        ]);

        // Security alert if suspicious activity detected
        if ($ipMetadata['is_vpn'] ?? false) {
            \Log::channel('security')->warning('Login from VPN/proxy detected', [
                'user_id' => $this->id,
                'email' => $this->email,
                'ip' => $realIp,
                'country' => $ipMetadata['country'] ?? 'Unknown',
            ]);
        }
    }

    /**
     * Detect device type from user agent
     */
    private function getDeviceType(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad|playbook|silk|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Detect platform from user agent
     */
    private function getPlatform(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        
        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) return 'macOS';
        if (str_contains($ua, 'linux')) return 'Linux';
        if (str_contains($ua, 'android')) return 'Android';
        if (preg_match('/iphone|ipad|ipod/', $ua)) return 'iOS';
        
        return 'Unknown';
    }

    /**
     * Check if login location is unusual (different country from last login)
     */
    public function isLoginLocationUnusual(string $currentIp): bool
    {
        $lastLogin = $this->auditLogs()
            ->where('event', 'user.login')
            ->latest()
            ->first();
            
        if (!$lastLogin) {
            return false; // First login
        }

        $lastMetadata = $lastLogin->metadata['ip_metadata'] ?? [];
        $currentMetadata = IpAddressHelper::getIpMetadata($currentIp);
        
        // Flag if country changed
        return ($lastMetadata['country'] ?? null) 
            && ($currentMetadata['country'] ?? null)
            && $lastMetadata['country'] !== $currentMetadata['country'];
    }

    /**
     * Check if device fingerprint changed (possible session hijacking)
     */
    public function isDeviceUnusual(string $currentFingerprint): bool
    {
        $lastLogin = $this->auditLogs()
            ->where('event', 'user.login')
            ->latest()
            ->first();
            
        if (!$lastLogin) {
            return false;
        }

        $lastFingerprint = $lastLogin->metadata['browser_fingerprint'] ?? null;
        
        return $lastFingerprint && $lastFingerprint !== $currentFingerprint;
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCOUNT MANAGEMENT
    // ──────────────────────────────────────────────────────────────────────

    public function markInactive(): void
    {
        $this->update(['is_active' => false]);
        
        // Log account deactivation
        AuditLog::create([
            'tenant_id' => $this->tenant_id,
            'event' => 'user.deactivated',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'actor_type' => 'system',
            'actor_name' => 'System',
        ]);
    }

    public function markActive(): void
    {
        $this->update(['is_active' => true]);
        
        // Log account activation
        AuditLog::create([
            'tenant_id' => $this->tenant_id,
            'event' => 'user.activated',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'actor_type' => 'system',
            'actor_name' => 'System',
        ]);
    }

    public function updatePassword(string $newPassword): void
    {
        $this->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ]);

        // Log password change
        AuditLog::create([
            'tenant_id' => $this->tenant_id,
            'event' => 'user.password_changed',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'actor_type' => 'user',
            'actor_id' => $this->id,
            'actor_name' => $this->name,
        ]);
    }

    public function hasPasswordChangedRecently(int $minutes = 30): bool
    {
        return $this->password_changed_at 
            && $this->password_changed_at->diffInMinutes(now()) < $minutes;
    }

    // ──────────────────────────────────────────────────────────────────────
    // MUTATORS (For Creating/Updating)
    // ──────────────────────────────────────────────────────────────────────

    public function setPasswordAttribute(string $value): void
    {
        // Only hash if the value doesn't look like it's already hashed
        if (!str_starts_with($value, '$2y$') && !str_starts_with($value, '$argon')) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function setPermissionsAttribute(array $value): void
    {
        $this->attributes['permissions'] = json_encode(array_unique($value));
    }

    // ──────────────────────────────────────────────────────────────────────
    // FACTORY (For Testing)
    // ──────────────────────────────────────────────────────────────────────

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}