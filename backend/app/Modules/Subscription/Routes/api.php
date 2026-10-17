<?php

use App\Modules\Subscription\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('subscriptions/current', [SubscriptionController::class, 'current']);
    Route::post('subscriptions/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::get('subscriptions/quotas', [SubscriptionController::class, 'quotas']);
});
