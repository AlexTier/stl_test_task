<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Slot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotService
{
    private const CACHE_KEY_AVAILABILITY = 'slots:availability';
    private const CACHE_TTL = 10;
    private const CACHE_LOCK_TIMEOUT = 5;

    private bool $cacheHit = false;

    public function getAvailability(): array
    {
        $cached = Cache::get(self::CACHE_KEY_AVAILABILITY);

        if ($cached !== null) {
            $this->cacheHit = true;
            return $cached;
        }

        $this->cacheHit = false;

        $lock = Cache::lock(
            self::CACHE_KEY_AVAILABILITY . ':lock',
            self::CACHE_LOCK_TIMEOUT
        );

        try {
            return $lock->block(self::CACHE_LOCK_TIMEOUT, function (){
                $cached = Cache::get(self::CACHE_KEY_AVAILABILITY);
                if ($cached !== null) {
                    return $cached;
                }

                $data = $this->fetchAvailabilityFromDatabase();

                Cache::put(
                    self::CACHE_KEY_AVAILABILITY,
                    $data,
                    self::CACHE_TTL
                );
                return $data;
            });

        } catch (\Illuminate\Contracts\Cache\LockTimeoutExeption $e) {
            Log::warning('Cache lock timeout for availability', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    private function fetchAvailabilityFromDatabase(): array
    {
        return Slot::available()
                ->get()
                ->map(function (Slot $slot) {
                    return [
                        'slot_id' => $slot->id,
                        'name' => $slot->name,
                        'capacity' => $slot->capacity,
                        'remaining' => $slot->remaining,
                    ];
                })
                ->values()
                ->all();
    }

    public function createHold(int $slotId,string $idempotencyKey): Hold
    {
        $existingHold = Hold::where('idempotency_key', $idempotencyKey)->first();

        if ($existingHold) {
            Log::info('Idempotency hold request', [
                'hold_id' => $existingHold,
                'idempotency_key' => $idempotencyKey
            ]);
            return $existingHold;
        }

        return DB::transaction(function () use ($slotId, $idempotencyKey) {
            $slot = Slot::where('id', $slotId)
                ->lockForUpdate()
                ->first();

            if (!$slot) {
                throw new \Exception('Slot noot found', 404);
            }

            if (!$slot->hasAvailability()) {
                throw new \Exception('No available capacity', 409);
            }
            $hold = Hold::create([
                'slot_id' => $slot->id,
                'idempotency_key' => $idempotencyKey,
                'status' => Hold::STATUS_HELD,
            ]);

            Log::info('Hold created', [
                'hold_id' => $hold->id,
                'slot_id' => $slot->id,
                'idempotency_key' => $idempotencyKey
            ]);

            $this->invalidateAvailabilityCache();

            return $hold;
        });
    }

    public function confirmHold(int $holdId): Hold
    {
        return DB::transaction(function () use ($holdId) {
            $hold = Hold::with('slot')
                ->where('id', $holdId)
                ->lockForUpdate()
                ->first();
            if (!$hold) {
                throw new \Exception('Hold not found', 404);
            }

            if ($hold->isConfirmed()) {
                throw new \Exception('Hold already confirmed', 409);
            }

            if ($hold->isCancelled()) {
                throw new \Exception('Hold is cancelled', 409);
            }

            $slot = Slot::where('id', $hold->slot_id)
                ->lockForUpdate()
                ->first();

            if (!$slot->hasAvailability()) {
                throw new \Exception('No available capacity', 409);
            }

            $slot->decrementRemaining()->save();
            $hold->setRelation('slot', $slot);

            $hold->confirm()->save();

            Log::info('Hold confirmed', [
                'hold_id' => $hold->id,
                'slot_id' => $slot->id,
                'remaining' => $slot->remaining
            ]);

            $this->invalidateAvailabilityCache();

            return $hold;
        });

    }

    public function cancelHold(int $holdId): Hold
    {
        return DB::transaction(function () use ($holdId) {
            $hold = Hold::with('slot')
                ->where('id', $holdId)
                ->lockForUpdate()
                ->first();
            if (!$hold) {
                throw new \Exception('Hold not found', 404);
            }

            if ($hold->isCancelled()) {
                return $hold;
            }

            $wasConfirmed = $hold->isConfirmed();

            if ($wasConfirmed) {
                $slot = Slot::where('id', $hold->slot_id)
                    ->lockForUpdate()
                    ->first();
                
                $slot->incrementRemaining()->save();
                $hold->setRelation('slot', $slot);
            }

            $hold->cancel()->save();

            Log::info('Hold cancelled', [
                'hold_id' => $hold->id,
                'slot_id' => $slot->id,
                'was_confirmed' => $wasConfirmed
            ]);

            $this->invalidateAvailabilityCache();

            return $hold;
        });
    }

    public function wasCacheHit(): bool
    {
        return $this->cacheHit;
    }

    function invalidateAvailabilityCache(): void
    {
        Cache::forget(self::CACHE_KEY_AVAILABILITY);

        Log::debug('Availability cache invallidated');
    }
}