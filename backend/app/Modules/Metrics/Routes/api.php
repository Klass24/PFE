<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Metrics\Controllers\MetricsController;

Route::get('/metrics', MetricsController::class);
