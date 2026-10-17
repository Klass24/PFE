<?php

use App\Modules\Pricing\Controllers\PricingController;
use Illuminate\Support\Facades\Route;

// ─── Public (pas besoin d'auth) ───────────────────────────────────────────────
Route::prefix('api/v1')->group(function () {
    Route::get('plans',        [PricingController::class, 'publicIndex']);
    Route::get('plans/{slug}', [PricingController::class, 'publicShow']);
});

// ─── Authenticated (tenant) ───────────────────────────────────────────────────
Route::prefix('api/v1/pricing')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('current',         [PricingController::class, 'current']);
    Route::get('compare/{slug}',  [PricingController::class, 'compare']);
});

// ─── Super Admin CRUD ─────────────────────────────────────────────────────────
Route::prefix('api/v1/admin/plans')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/',          [PricingController::class, 'index']);
    Route::get('{uuid}',    [PricingController::class, 'show']);
    Route::post('/',         [PricingController::class, 'store']);
    Route::put('{uuid}',    [PricingController::class, 'update']);
    Route::delete('{uuid}', [PricingController::class, 'destroy']);
});
