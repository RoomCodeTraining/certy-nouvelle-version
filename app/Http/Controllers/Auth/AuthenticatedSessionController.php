<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExternalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $externalService = app(ExternalService::class);

        if (! $externalService->baseUrl) {
            throw ValidationException::withMessages([
                'email' => ['Service d\'authentification externe non configuré (ASACI_CORE_URL).'],
            ]);
        }

        $authData = $externalService->auth($request->input('email'), $request->input('password'));

        $token = $authData['token'] ?? $authData['access_token'] ?? $authData['data']['token'] ?? null;

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Ces identifiants ne correspondent pas à nos enregistrements.'],
            ]);
        }

        $raw = $externalService->getUser($token);

        if ($raw === null || ! is_array($raw)) {
            Log::warning('Auth: getUser a retourné null ou non-array', ['baseUrl' => $externalService->baseUrl]);
            throw ValidationException::withMessages([
                'email' => ['Réponse invalide du service externe (auth/user). Vérifiez storage/logs/laravel.log'],
            ]);
        }

        // Structure API : { status, message, data: { id, email, username, name, first_name, last_name, organization, current_office, role, ... } }
        $userData = isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : $raw;

        $email = $userData['email'] ?? $userData['mail'] ?? null;
        if (! $email) {
            Log::debug('Auth: profil utilisateur sans email', ['userData' => $userData]);
            throw ValidationException::withMessages([
                'email' => ['Impossible de récupérer le profil utilisateur (email manquant dans la réponse).'],
            ]);
        }

        $user = $this->syncUser($email, $userData, $token, $authData);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'Vous êtes connecté.');
    }

    /**
     * Synchronise l'utilisateur local avec le profil ASACI (auth/user).
     * userData = payload utilisateur (data de la réponse API).
     * Structure : id, email, username, name, first_name, last_name, organization: { code, name }, role: { name, label }, ...
     */
    private function syncUser(string $email, array $userData, string $token, array $authData): User
    {
        $name = $userData['name']
            ?? trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''))
            ?: $userData['full_name']
            ?? $userData['firstName']
            ?? $email;
        if (is_array($name)) {
            $name = $name['first'] ?? $email;
        }

        $expiresAt = $authData['expires_at'] ?? $authData['expiresAt'] ?? $authData['data']['expires_at'] ?? null;
        if (is_string($expiresAt)) {
            $expiresAt = Carbon::parse($expiresAt);
        } elseif ($expiresAt !== null && ! $expiresAt instanceof Carbon) {
            $expiresAt = null;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            ]
        );

        $username = $userData['username']
            ?? $userData['code_demandeur']
            ?? $userData['code']
            ?? $userData['identifier']
            ?? null;

        $organization = $userData['organization'] ?? null;
        $entityCode = $userData['entity_code']
            ?? $userData['code_entite']
            ?? $userData['organization_code']
            ?? (is_array($organization) ? ($organization['code'] ?? null) : null)
            ?? (is_array($userData['relationship'] ?? null) ? ($userData['relationship']['code'] ?? null) : null);

        $currentOffice = $userData['current_office'] ?? $userData['office'] ?? null;
        $officeCode = $userData['office_code']
            ?? (is_array($currentOffice) ? ($currentOffice['code'] ?? null) : null)
            ?? null;

        $role = $userData['role'] ?? null;
        $roleCode = is_array($role) ? ($role['name'] ?? $role['code'] ?? null) : ($role !== null ? (string) $role : null);
        $roleName = is_array($role) ? ($role['label'] ?? $role['name'] ?? null) : null;
        $isRoot = $roleCode === 'main_office_admin' || $roleName === 'main_office_admin';

        $user->update([
            'name' => $name,
            'external_token' => $token,
            'external_token_expires_at' => $expiresAt,
            'external_username' => $username,
            'external_entity_code' => $entityCode,
            'office_code' => $officeCode,
            'user_role_code' => $roleCode,
            'user_role_name' => $roleName,
            'is_root' => $isRoot,
        ]);

        return $user;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Vous êtes déconnecté.');
    }
}
