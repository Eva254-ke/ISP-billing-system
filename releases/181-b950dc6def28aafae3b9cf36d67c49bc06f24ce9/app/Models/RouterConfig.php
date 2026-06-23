<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'config_type',
        'config_content',
        'config_hash',
        'status',
        'deployed_at',
        'deployment_error',
        'previous_config',
        'can_rollback',
    ];

    protected $casts = [
        'deployed_at' => 'datetime',
        'can_rollback' => 'boolean',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDeployed($query)
    {
        return $query->where('status', 'deployed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeLatest($query)
    {
        return $query->latest('created_at');
    }
}