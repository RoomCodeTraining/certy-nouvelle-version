<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequestTimeout
{
    /**
     * Limite le temps d'exécution de la requête pour garantir des réponses < 5s.
     * Désactiver en mettant request_max_seconds à 0 dans config/app.php.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $limit = (int) config('app.request_max_seconds', 5);
        if ($limit > 0) {
            set_time_limit($limit);
        }

        return $next($request);
    }
}
