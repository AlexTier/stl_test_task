<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hold extends Model
{
    public const STATUS_HELD = 'held';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public const HOLD_LEFTIME_MINUTES = 5;

    protected $table = 'holds';
    
    protected $fillable = [
        'slot_id',
        'idempotency_key',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isHeld(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function confirm(): self
    {
        $this->status = self::STATUS_CONFIRMED;
        return $this;
    }

    public function cancel(): self
    {
        $this->status = self::STATUS_CANCELLED;
        return $this;
    }

    public function scopeHeld($query)
    {
        return $query->where('status', self::STATUS_HELD);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hold) {
            if (!$hold->expires_at) {
                $hold->expires_at = now()->addMinutes(self::HOLD_LEFTIME_MINUTES);
            }
        });
    }
}