<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé au super administrateur.',
            ], 403);
        }

        return $next($request);
    }
}
