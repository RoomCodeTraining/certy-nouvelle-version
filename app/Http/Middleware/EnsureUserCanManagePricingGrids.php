<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManagePricingGrids
{
    /**
     * Accès réservé aux root (main_office_admin) ou office_admin.
     * Permet de consulter et modifier les grilles tarifaires.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Accès réservé aux utilisateurs authentifiés.');
        }
        if (! $user->isRoot() && ! $user->isOfficeAdmin()) {
            abort(403, 'Accès réservé aux administrateurs (main_office_admin ou office_admin).');
        }

        return $next($request);
    }
}
