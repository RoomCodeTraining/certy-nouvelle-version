<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->currentOrganization()) {
            return $next($request);
        }

        $name = 'Mon cabinet';
        $slug = Str::slug($name).'-'.Str::lower(Str::random(6));
        $org = Organization::create([
            'name' => $name,
            'slug' => $slug,
        ]);
        $org->users()->attach($user->id, ['role' => 'admin']);

        return $next($request);
    }
}
