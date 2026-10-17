<?php

use App\Modules\Payment\Controllers\PaymentController;
use App\Modules\Payment\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// ─── Webhooks (no auth — vérification signature dans controller) ──────────────
Route::prefix('api/v1/webhooks')->group(function () {
    Route::post('stripe', [WebhookController::class, 'stripe']);
    Route::post('paypal', [WebhookController::class, 'paypal']);
});

// ─── Tenant (company admin) ──────────────────────────────────────────────────
Route::prefix('api/v1/payments')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('checkout',        [PaymentController::class, 'checkout']);
    Route::post('paypal/capture',  [PaymentController::class, 'capturePayPal']);
    Route::get('history',          [PaymentController::class, 'history']);
});

// ─── Super Admin ─────────────────────────────────────────────────────────────
Route::prefix('api/v1/admin/payments')->middleware(['auth:sanctum', 'super_admin'])->group(function () {
    Route::get('/',       [PaymentController::class, 'adminIndex']);
    Route::get('{uuid}',  [PaymentController::class, 'adminShow']);
});
