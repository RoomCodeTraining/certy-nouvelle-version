# Authentification API via ASACI

## Flux

```
Frontend                    Backend Laravel                    API externe (ASACI Core)
   |                              |                                    |
   |  POST /api/v1/login          |                                    |
   |  { email, password }         |                                    |
   |  --------------------------> |  POST {baseUrl}/auth/tokens         |
   |                              |  { email, password }                |
   |                              |  --------------------------------->|
   |                              |                                    |
   |                              |  <------ token + expires_at -------|
   |                              |                                    |
   |                              |  GET {baseUrl}/auth/user            |
   |                              |  Authorization: Bearer {token}      |
   |                              |  --------------------------------->|
   |                              |                                    |
   |                              |  <-------- user data ---------------|
   |                              |                                    |
   |                              |  User::updateOrCreate(...)          |
   |                              |  - Sync user local                  |
   |                              |  - Sauvegarder external_token       |
   |                              |  - external_token_expires_at        |
   |                              |                                    |
   |                              |  $user->createToken('auth_token')   |
   |                              |  ->plainTextToken                   |
   |                              |                                    |
   |  { user, token }             |                                    |
   |  <-------------------------- |                                    |
```

## Endpoint

- **POST** `/api/v1/login`
- **Body (JSON)** : `{ "email": "...", "password": "..." }`
- **Réponse 200** : `{ "user": { ... }, "token": "...", "token_type": "Bearer" }`
- **Réponse 422** : erreurs de validation ou identifiants invalides / ASACI indisponible

Le frontend doit ensuite envoyer `Authorization: Bearer {token}` sur les requêtes API protégées.

## Configuration

- **.env** : `ASACI_BASE_URL=https://api.asaci.ci` (ou l’URL réelle de l’API ASACI)
- **Config** : `config/services.php` → `asaci.base_url`

## Mapping ASACI → User local

Le service `AsaciAuthService` suppose que :

- **POST auth/tokens** retourne au moins `token` (ou `access_token`) et optionnellement `expires_at` / `expiresAt`.
- **GET auth/user** retourne au moins `email` (ou `mail`) et optionnellement `name` (ou `full_name`, `firstName`).

Adapter dans `AsaciAuthService` et dans `LoginController::syncUser()` / `userResource()` si les champs ASACI diffèrent.

## Installation

```bash
ddev composer update   # installe laravel/sanctum
ddev php artisan migrate   # exécute les migrations (dont personal_access_tokens si publiée)
```

Si la migration Sanctum n’existe pas : `ddev php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` puis `ddev php artisan migrate`.
