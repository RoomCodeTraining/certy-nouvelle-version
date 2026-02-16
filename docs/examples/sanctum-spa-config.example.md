# Sanctum mode SPA (exemple de configuration)

## 1. Fichier `config/sanctum.php`

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

En fullstack, le frontend est servi par Laravel (même origine). Votre domaine (ex. `certy.test` ou `localhost`) doit être dans `SANCTUM_STATEFUL_DOMAINS`.

## 2. Middleware `EnsureFrontendRequestsAreStateful`

Pour que les requêtes SPA soient considérées comme stateful (session + cookies), Sanctum fournit ce middleware. En Laravel 11+, il est souvent enregistré dans `bootstrap/app.php` ou dans le groupe `api`. Pour Inertia, les requêtes passent par les routes **web**, pas `api`. Donc :

- Soit vous n’utilisez **pas** les routes `api` pour le frontend : vous utilisez uniquement les routes **web** avec le guard **web** (session). Dans ce cas, pas besoin de `EnsureFrontendRequestsAreStateful` pour les pages Inertia : la session web suffit.
- Soit votre frontend (avant migration) appelait l’API avec des cookies : alors le groupe qui reçoit ces appels (ex. `api`) doit avoir `EnsureFrontendRequestsAreStateful` en premier.

En pratique, pour une app **fullstack Inertia** :

- Toutes les requêtes du navigateur vont vers des **routes web** (GET/POST avec CSRF, cookie de session).
- Le guard par défaut pour ces routes est **web** (session). Pas besoin de token Bearer.
- Donc : **pas de changement particulier** dans Sanctum si vous n’exposez plus d’API pour le même frontend. Si vous gardez des routes API pour d’autres clients (mobile, externe), laissez-les en guard `sanctum` (token) et ne mettez pas `EnsureFrontendRequestsAreStateful` sur ces routes.

Résumé : en fullstack Inertia (même domaine, routes web, session), l’auth fonctionne comme une app Laravel classique (session). Sanctum en mode SPA est utile si votre frontend était sur un autre sous-domaine (ex. `app.monapp.com`) et appelait l’API avec des cookies ; dans ce cas, ajoutez ce domaine dans `stateful` et utilisez le middleware sur le groupe concerné.

## 3. `.env` exemple

```env
SESSION_DOMAIN=localhost
# ou .certy.test si vous utilisez un vhost
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## 4. Inactivité et redirection login

Si après 30 min le middleware `check.inactivity` supprime la session et renvoie une redirection vers `/login`, Inertia suivra cette redirection. Si vous renvoyez du JSON 401, le frontend peut intercepter et faire `router.visit('/login')`. Préférez une **redirect** Laravel vers `/login` pour rester cohérent avec Inertia.
