<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'router_id',
        'valid_from',
        'valid_until',
        'validity_hours',
        'batch_id',
        'batch_name',
        'printed',
        'printed_at',
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
    ];

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

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeUnused($query)
    {
        return $query->where('status', 'unused');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('valid_until', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'unused')
            ->where('valid_until', '>', now());
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until->isPast() || $this->status === 'expired';
    }

    public function getIsUsableAttribute(): bool
    {
        return $this->status === 'unused' && $this->valid_until->isFuture();
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function markAsUsed(string $phone, string $mac, int $routerId): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_by_phone' => $phone,
            'used_by_mac' => $mac,
            'router_id' => $routerId,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    public function markAsPrinted(): void
    {
        $this->update([
            'printed' => true,
            'printed_at' => now(),
        ]);
    }
}