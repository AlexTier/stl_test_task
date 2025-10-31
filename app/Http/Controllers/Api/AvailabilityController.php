<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private SlotService $slotService
    ) {}

    public function index(): JsonResponse
    {
        $availability = $this->slotService->getAvailability();

        $cacheHit = $this->slotService->wasCacheHit();

        return response()->json($availability)
            ->header('X-Cache-Hit', $cacheHit ? 'true' : 'false');
    }
}