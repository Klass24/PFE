<?php

use App\Modules\Admin\Controllers\AdminCompanyController;
use App\Modules\Admin\Controllers\AdminDashboardController;
use App\Modules\Admin\Controllers\AdminSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')->middleware(['auth:sanctum', 'super_admin'])->group(function () {
    // Dashboard stats
    Route::get('stats', [AdminDashboardController::class, 'stats']);

    // Companies CRUD
    Route::get('companies', [AdminCompanyController::class, 'index']);
    Route::post('companies', [AdminCompanyController::class, 'store']);
    Route::get('companies/{uuid}', [AdminCompanyController::class, 'show']);
    Route::put('companies/{uuid}', [AdminCompanyController::class, 'update']);
    Route::put('companies/{uuid}/suspend', [AdminCompanyController::class, 'suspend']);
    Route::put('companies/{uuid}/activate', [AdminCompanyController::class, 'activate']);
    Route::delete('companies/{uuid}', [AdminCompanyController::class, 'destroy']);

    // Subscriptions — vue globale
    Route::get('subscriptions', [AdminSubscriptionController::class, 'all']);
    Route::put('subscriptions/{id}', [AdminSubscriptionController::class, 'update']);
    Route::delete('subscriptions/{id}', [AdminSubscriptionController::class, 'destroy']);

    // Subscriptions — par entreprise
    Route::get('companies/{uuid}/subscriptions', [AdminSubscriptionController::class, 'index']);
    Route::post('companies/{uuid}/subscriptions', [AdminSubscriptionController::class, 'store']);
    Route::get('companies/{uuid}/subscriptions/current', [AdminSubscriptionController::class, 'current']);
    Route::post('companies/{uuid}/subscriptions/change-plan', [AdminSubscriptionController::class, 'changePlan']);
    Route::get('companies/{uuid}/quotas', [AdminSubscriptionController::class, 'quotas']);
});
