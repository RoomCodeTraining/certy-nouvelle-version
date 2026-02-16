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

        $userData = $externalService->getUser($token);

        if ($userData === null) {
            Log::warning('Auth: getUser a retourné null', ['baseUrl' => $externalService->baseUrl]);
            throw ValidationException::withMessages([
                'email' => ['Réponse invalide du service externe (auth/user). Vérifiez storage/logs/laravel.log'],
            ]);
        }

        $email = $userData['email']
            ?? $userData['mail']
            ?? (is_array($userData['data'] ?? null) ? ($userData['data']['email'] ?? $userData['data']['mail'] ?? null) : null)
            ?? (is_array($userData['user'] ?? null) ? ($userData['user']['email'] ?? $userData['user']['mail'] ?? null) : null);
        if (! $email) {
            Log::debug('Auth: profil utilisateur sans email', ['userData' => $userData]);
            throw ValidationException::withMessages([
                'email' => ['Impossible de récupérer le profil utilisateur (email manquant dans la réponse). Vérifiez storage/logs/laravel.log pour la structure renvoyée.'],
            ]);
        }

        $user = $this->syncUser($email, $userData, $token, $authData);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'Vous êtes connecté.');
    }

    private function syncUser(string $email, array $userData, string $token, array $authData): User
    {
        $name = $userData['name'] ?? $userData['full_name'] ?? $userData['firstName'] ?? $email;
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
        $relationship = $userData['relationship'] ?? null;
        $entityCode = $userData['entity_code']
            ?? $userData['code_entite']
            ?? $userData['organization_code']
            ?? (is_array($relationship) ? ($relationship['code'] ?? null) : null)
            ?? null;

        $role = $userData['role'] ?? null;
        $roleCode = is_array($role) ? ($role['name'] ?? $role['code'] ?? null) : null;
        $roleName = is_array($role) ? ($role['label'] ?? $role['name'] ?? null) : null;
        $isRoot = $roleCode === 'main_office_admin' || $roleName === 'main_office_admin';

        $user->update([
            'name' => $name,
            'external_token' => $token,
            'external_token_expires_at' => $expiresAt,
            'external_username' => $username,
            'external_entity_code' => $entityCode,
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
