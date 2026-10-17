<?php

use Illuminate\Support\Facades\Route;

// routes/api.php — Point d'entrée : charge tous les modules

// Health check pour les liveness/readiness probes Kubernetes (pas d'auth, pas de DB requise)
Route::get('/health', fn () => response()->json(['status' => 'ok'], 200));

foreach (glob(app_path('Modules/*/Routes/api.php')) as $moduleRouteFile) {
    require $moduleRouteFile;
}
