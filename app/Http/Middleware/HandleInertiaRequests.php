<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name', 'Certy'),
                'logo' => config('app.logo') ? asset(config('app.logo')) : null,
            ],
            'flash' => [
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
                'validation_errors' => $request->session()->get('validation_errors', []),
            ],
            'auth' => [
                'user' => $request->user() ? (function () use ($request) {
                    $user = $request->user();
                    $user->load('organizations');
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_root' => $user->isRoot(),
                        'onboarding_completed' => $user->hasCompletedOnboarding(),
                        'current_organization' => $user->currentOrganization()?->only(['id', 'name', 'slug']),
                    ];
                })() : null,
            ],
        ];
    }
}
