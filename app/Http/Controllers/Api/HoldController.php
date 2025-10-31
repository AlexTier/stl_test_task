<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HoldController extends Controller
{
    public function __construct(
        private SlotService $slotService
    ) {}

    public function store(Request $request, int $slotId): JsonResponse
    {

        $idempotencyKey = $request->header('Idempotency-Key');

        $validator = Validator::make(
            ['idempotency_key' => $idempotencyKey],
            ['idempotency_key' => 'required|uuid']
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid or missing Idempotency-Key header',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $hold = $this->slotService->createHold($slotId, $idempotencyKey);

            return response()->json($hold->toArray(), 201)
                ->header('X-Data-Source', 'database');

        } catch(\Exeption $e){
            $statusCode = $e->getCode() ? : 500;
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function confirm(int $holdId): JsonResponse
    {
        try {
            $hold = $this->slotService->confirmHold($holdId);

            return response()->json($hold->toArray(), 201)
                ->header('X-Data-Source', 'database');

        } catch(\Exeption $e){

            $statusCode = $e->getCode() ? : 500;
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);

        }
    }

    public function destroy(int $holdId): JsonResponse
    {
        try {
            $hold = $this->slotService->cancelHold($holdId);

            return response()->json($hold->toArray(), 201)
                ->header('X-Data-Source', 'database');

        } catch(\Exeption $e){

            $statusCode = $e->getCode() ? : 500;
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }
}