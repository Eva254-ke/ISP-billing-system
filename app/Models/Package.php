<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory, SoftDeletes;

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
        return "{$this->duration_value} {$this->duration_unit}";
    }

    public function getBandwidthFormattedAttribute(): string
    {
        $down = $this->download_limit_mbps ?? '∞';
        $up = $this->upload_limit_mbps ?? '∞';
        return "{$down}↓ / {$up}↑ Mbps";
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function incrementSales(float $amount): void
    {
        $this->increment('total_sales');
        $this->increment('total_revenue', $amount);
    }

    public function getMikroTikRateLimit(): string
    {
        $download = $this->download_limit_mbps ?? '100M';
        $upload = $this->upload_limit_mbps ?? '100M';
        return "{$download}M/{$upload}M";
    }
}