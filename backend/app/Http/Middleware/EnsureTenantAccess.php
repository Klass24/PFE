<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Accès refusé : tenant non défini.',
            ], 403);
        }

        // M1 — Bloquer l'accès si l'entreprise est suspendue
        $company = $user->company;
        if ($company && $company->isSuspended()) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Votre compte entreprise est suspendu. Contactez le support.',
            ], 403);
        }

        return $next($request);
    }
}
