<?php

use App\Modules\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get ('notifications',              [NotificationController::class, 'index']);
    Route::get ('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all',     [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{uuid}/read',  [NotificationController::class, 'markAsRead']);
});
