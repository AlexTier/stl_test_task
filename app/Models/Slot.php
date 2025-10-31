<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    protected $table = 'slots';
    
    protected $fillable = [
        'name',
        'capacity',
        'remaining',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'remaining' => 'integer',
    ];

    public function holds(): HasMany
    {
        return $this->HasMany(Hold::class);
    }

    public function hasAvailability(): bool
    {
        return $this->remaining > 0;
    }

    public function decrementRemaining(int $count = 1): self
    {
        $this->remaining = max(0, $this->remaining - $count);
        return $this;
    }

    public function incrementRemaining(int $count = 1): self
    {
        $this->remaining = min($this->capacity, $this->remaining + $count);
        return $this;
    }

    public function scopeAvailable($query)
    {
        return $query->where('remaining', '>', 0);
    }
}