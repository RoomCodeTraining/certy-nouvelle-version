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

    /**
     * Transforme les messages d'erreur techniques en messages utilisateur.
     */
    private function formatFlashError(?string $error): ?string
    {
        if (! $error) {
            return null;
        }
        $lower = strtolower($error);
        if (str_contains($lower, 'unauthorized') || str_contains($lower, '401')) {
            return 'Session expirée. Veuillez vous reconnecter.';
        }
        if (str_contains($lower, 'forbidden') || str_contains($lower, '403')) {
            return "Vous n'avez pas les droits pour effectuer cette action.";
        }
        if (str_contains($lower, 'not found') || str_contains($lower, '404')) {
            return 'Élément introuvable.';
        }
        if (str_contains($lower, 'server error') || str_contains($lower, '500')) {
            return 'Erreur serveur. Veuillez réessayer plus tard.';
        }

        return $error;
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
                'error' => $this->formatFlashError($request->session()->get('error')),
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
            'certy_ia' => $request->user() ? [
                'name' => config('certy.name', 'Certy IA'),
                'enabled' => config('certy.enabled', false),
                'organization_enabled' => $request->user()->currentOrganization()?->certy_ia_enabled ?? false,
            ] : null,
        ];
    }
}
