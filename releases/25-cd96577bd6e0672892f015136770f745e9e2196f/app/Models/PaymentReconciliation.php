<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'reconciliation_date',
        'reconciliation_time',
        'dashboard_total',
        'mpesa_total',
        'bank_total',
        'discrepancy_amount',
        'discrepancy_percentage',
        'status',
        'total_transactions',
        'matched_transactions',
        'missing_in_dashboard',
        'missing_in_mpesa',
        'amount_mismatches',
        'discrepancy_details',
        'notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'reconciliation_time' => 'string',
        'dashboard_total' => 'decimal:2',
        'mpesa_total' => 'decimal:2',
        'bank_total' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
        'discrepancy_percentage' => 'decimal:2',
        'discrepancy_details' => 'array',
        'resolved_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }

    public function scopeDiscrepancy($query)
    {
        return $query->where('status', 'discrepancy');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────

    public function markResolved(int $userId, string $notes = null): void
    {
        $this->update([
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'notes' => $notes,
        ]);
    }
}