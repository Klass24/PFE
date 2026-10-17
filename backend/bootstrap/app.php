<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant'      => \App\Http\Middleware\EnsureTenantAccess::class,
            'role'        => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return match (true) {
                    $e instanceof ModelNotFoundException    => response()->json(['success' => false, 'data' => null, 'message' => 'Ressource introuvable.'], 404),
                    $e instanceof AuthorizationException    => response()->json(['success' => false, 'data' => null, 'message' => 'Action non autorisée.'], 403),
                    $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403 => response()->json(['success' => false, 'data' => null, 'message' => 'Action non autorisée.'], 403),
                    $e instanceof AuthenticationException   => response()->json(['success' => false, 'data' => null, 'message' => 'Non authentifié.'], 401),
                    $e instanceof ValidationException       => response()->json(['success' => false, 'data' => $e->errors(), 'message' => 'Données invalides.'], 422),
                    default                                 => response()->json(['success' => false, 'data' => null, 'message' => 'Erreur interne du serveur.'], 500),
                };
            }
        });
    })->create();
