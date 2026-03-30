<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'status',
        'callback_data',
        'callback_attempts',
        'initiated_at',
        'confirmed_at',
        'completed_at',
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
        'callback_attempts' => 'integer',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
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

    public function session(): HasOne
    {
        return $this->hasOne(UserSession::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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

    // ──────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ──────────────────────────────────────────────────────────────────────

    public function getIsReconcilableAttribute(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'refunded']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPER METHODS (STATE MACHINE)
    // ──────────────────────────────────────────────────────────────────────

    public function markPending(): void
    {
        $this->update(['status' => 'pending']);
    }

    public function markConfirmed(array $callbackData): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'callback_data' => $callbackData,
        ]);
    }

    public function markCompleted(UserSession $session): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'session_id' => $session->id,
        ]);
    }

    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'reconciliation_notes' => $reason,
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
}