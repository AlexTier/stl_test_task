<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\HoldController;
use Illuminate\Support\Facades\Route;

// GET /slots/availability
Route::get('/slots/availability', [AvailabilityController::class, 'index']);

// POST /slots/{id}/hold
Route::post('/slots/{id}/hold', [HoldController::class, 'store']);

// POST /holds/{id}/confirm
Route::post('/holds/{id}/confirm', [HoldController::class, 'confirm']);

// DELETE /holds/{id}
Route::delete('/holds/{id}', [HoldController::class, 'destroy']);