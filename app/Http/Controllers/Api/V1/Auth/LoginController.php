<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExternalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/v1/login
     * Authentification via le service externe (ASACI), sync user local, retourne token Sanctum si installé.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $externalService = app(ExternalService::class);

        if (! $externalService->baseUrl) {
            throw ValidationException::withMessages([
                'email' => ['Service externe non configuré (ASACI_CORE_URL).'],
            ]);
        }

        $authData = $externalService->auth($request->input('email'), $request->input('password'));
        $token = $authData['token'] ?? $authData['access_token'] ?? $authData['data']['token'] ?? null;

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        $userData = $externalService->getUser($token);
        $email = $userData['email'] ?? $userData['mail'] ?? null;

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => ['Impossible de récupérer le profil utilisateur.'],
            ]);
        }

        $user = $this->syncUser($email, $userData, $token, $authData);

        $authToken = null;
        if (method_exists($user, 'createToken')) {
            $authToken = $user->createToken('auth_token')->plainTextToken;
        }

        if (! $authToken) {
            return response()->json([
                'message' => 'Authentification API nécessite Laravel Sanctum. Exécutez: composer require laravel/sanctum puis ajoutez le trait HasApiTokens au modèle User.',
                'user' => $this->userResource($user),
            ], 503);
        }

        return response()->json([
            'user' => $this->userResource($user),
            'token' => $authToken,
            'token_type' => 'Bearer',
        ]);
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
            ['name' => $name, 'password' => Hash::make(\Illuminate\Support\Str::random(32))]
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

    private function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'onboarding_completed' => $user->hasCompletedOnboarding(),
            'current_organization' => $user->currentOrganization()?->only(['id', 'name', 'slug']),
        ];
    }
}
