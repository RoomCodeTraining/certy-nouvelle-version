# Migration API Laravel → Fullstack Inertia.js

Plan de migration d’une API Laravel 12 (backend) vers une application fullstack Laravel + Inertia.js (Vue 3 ou React) avec Sanctum en mode SPA.

---

## 1. Configuration Inertia.js + Laravel

### 1.1 Installer les paquets

```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/vue3 vue  # ou @inertiajs/react react pour React
```

### 1.2 Driver frontend (Vite)

- **Vite** : déjà utilisé par Laravel ; configurer l’entrée sur `resources/js/app.js` (Vue) ou `resources/js/app.jsx` (React).
- **Alias** : dans `vite.config.js`, `'@' => path.resolve(__dirname, 'resources/js')`.
- **Plugins** : `laravel-vite-plugin`, `@vitejs/plugin-vue` (ou plugin React).

### 1.3 Middleware Inertia

- Créer `app/Http/Middleware/HandleInertiaRequests.php` (étendre `Inertia\Middleware`).
- Définir `rootView` (ex. `'app'`).
- Dans `share()` : passer `auth.user`, `flash`, et toute donnée globale (filtres, références partagées).

Enregistrer dans le groupe `web` :

```php
// bootstrap/app.php (Laravel 11+)
$middleware->web(append: [
    \App\Http\Middleware\HandleInertiaRequests::class,
]);
```

### 1.4 Layout Blade de base

- Une seule vue Blade (ex. `resources/views/app.blade.php`) :
  - `@vite(['resources/css/app.css', 'resources/js/app.js'])`
  - `<meta name="csrf-token" content="{{ csrf_token() }}">`
  - `<div id="app" data-page="{{ json_encode($page) }}"></div>` **ou** `@inertia` (fourni par le package).

### 1.5 Point d’entrée JS (Vue 3)

- `resources/js/app.js` : créer l’app Vue, utiliser `createInertiaApp()` de `@inertiajs/vue3`, résoudre la page avec `resolvePageComponent`, enregistrer le layout global si besoin.
- Toutes les routes SPA rendent une seule page Inertia ; les « pages » sont des composants (ex. `Pages/Dashboard.vue`, `Pages/Clients/Index.vue`).

### 1.6 Sanctum en mode SPA

- **Cookies** : le frontend est servi depuis le même domaine que l’API (même app Laravel), donc cookies de session + CSRF.
- **Config** : dans `config/sanctum.php`, `stateful` doit inclure le domaine du frontend (ex. `localhost`, ou le domaine de prod).
- **CORS** : si le front était sur un autre domaine, il faudrait CORS + cookies `SameSite` ; en fullstack même domaine, pas de CORS nécessaire.
- **Auth** : utiliser le guard `web` (session) pour les routes Inertia, pas le guard `sanctum` (token) pour la navigation classique. Pour les utilisateurs venant de l’API externe (SSO), conserver un flux qui enregistre la session après validation du token externe (voir § 5).

---

## 2. Routes Web (remplaçant l’API)

Créer `routes/web.php` avec des routes **web** (middleware `web`, session, CSRF). Ne pas exposer de JSON pour ces écrans : les contrôleurs retournent `Inertia::render('NomPage', [...])`.

### 2.1 Auth

| Méthode | URI        | Action        | Nom          |
|--------|------------|---------------|--------------|
| GET    | login      | create        | login        |
| POST   | login      | store         | -            |
| POST   | logout     | destroy       | logout       |
| GET    | register   | create        | register     |
| POST   | register   | store         | -            |

### 2.2 Dashboard

| GET | /dashboard | DashboardController (invokable ou index) | dashboard |

### 2.3 Clients

| Méthode | URI | Action | Nom |
|--------|-----|--------|-----|
| GET | /clients | index | clients.index |
| GET | /clients/create | create | clients.create |
| POST | /clients | store | clients.store |
| GET | /clients/{client} | show | clients.show |
| GET | /clients/{client}/edit | edit | clients.edit |
| PUT/PATCH | /clients/{client} | update | clients.update |
| DELETE | /clients/{client} | destroy | clients.destroy |

Utiliser le binding par HashId si vous utilisez `deligoez/laravel-model-hashid` (route model binding personnalisé ou résolution manuelle dans le contrôleur).

### 2.4 Véhicules

- CRUD : `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Route dédiée si besoin : `GET /clients/{client}/vehicles` → véhicules du client (pour select, sous-formulaire, etc.).

### 2.5 Contrats

- CRUD + actions métier : `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Actions : `validate`, `cancel`, `renew`, `endorse`.
- PDF : `printedPdf`, `downloadPdf` (retourner un fichier ou une URL signée).

### 2.6 Bordereaux

- CRUD + `submit`, `approve`, `reject`, `markAsPaid`, `generatePdf`.

### 2.7 Productions

- `index`, `summary`, `export` (export fichier ou téléchargement).

### 2.8 Reporting

- `contracts-overview`, `statistics-by-period`, `expiring-contracts`, etc. → chaque route = une page Inertia avec les props nécessaires (période, agrégats, liste de contrats).

### 2.9 Références

- Soit garder les endpoints API (ex. `GET /api/vehicle-brands`, `GET /api/vehicle-models`) pour le frontend (appels fetch depuis les pages Inertia).
- Soit exposer les références via les props Inertia (ex. passer `brands`, `models` dans la page `Contracts/Create.vue`). Préférer les props pour éviter des allers-retours inutiles.

### 2.10 Comptabilité

- CRUD `accounting_entries` : routes web classiques (index, create, store, show, edit, update, destroy).

### 2.11 Paramètres courtier

- `GET/PUT /broker-settings` (ou `settings/broker`) → une page Inertia avec formulaire.

### 2.12 Middleware

- Appliquer `auth` (ou `auth:sanctum` si vous gardez Sanctum pour la session SPA — voir § 5) sur toutes les routes protégées.
- Appliquer `check.inactivity` (session expirée après 30 min) sur le groupe protégé.
- Pour les routes SSO/externe : garder le middleware `external.sso` sur les endpoints qui doivent vérifier le token externe.

---

## 3. Contrôleurs Inertia

- Un contrôleur par ressource (ou par groupe logique).
- Chaque action qui affiche un écran retourne `Inertia::render('NomDuComposant', $props)`.
- **Réutiliser** :
  - Les **Actions** (ex. `CreateClientAction`, `ValidateContractAction`).
  - Les **Services** (ex. `ContractPricingService`).
  - Les **FormRequests** pour la validation (injectés dans les méthodes de contrôleur).
- Ne pas dupliquer la logique métier : le contrôleur appelle l’Action ou le Service, puis passe les données à la vue Inertia.
- Pour les listes : utiliser les mêmes **scopes** et **filtres** (ex. `accessibleBy`, `useFilters`) que dans l’API, puis paginer et passer `items`, `filters`, `links` en props.
- **ExternalController** : adapter pour qu’il puisse être appelé depuis des pages Inertia (formulaires, boutons) tout en gardant la logique SSO/API externe (certificats, transactions). Soit les actions restent en POST/GET web et retournent des redirections Inertia avec flash, soit elles retournent du JSON si appelées en AJAX depuis Inertia ; à décider selon l’UX.

---

## 4. Vues Inertia (pages)

- **Structure** : par ressource, ex. `Pages/Clients/Index.vue`, `Pages/Clients/Create.vue`, `Pages/Clients/Show.vue`, `Pages/Contracts/Index.vue`, etc.
- **Layout** : un (ou plusieurs) layout(s) partagé(s) (ex. `Layouts/AppLayout.vue`) avec menu, header, zone pour `<slot />` ou `<main>`.
- **Props** : utiliser les props passées depuis le contrôleur (liste, item, références, erreurs de validation).
- **Formulaires** :
  - Utiliser `useForm` (Vue) ou équivalent pour lier les champs et envoyer en POST/PUT.
  - En cas d’erreur 422, Inertia renvoie les erreurs ; les afficher à côté des champs (ex. `form.errors.email`).
  - Conserver les anciennes valeurs avec `old()` côté Laravel ou via la prop `errors` + valeurs dans la page.
- **Navigation** : `router.visit()`, `Link` (composant Inertia), et gestion du chargement (indicateur global ou par bouton) avec les événements Inertia (`start`, `finish`).
- **États de chargement** : désactiver les boutons ou afficher un spinner pendant les requêtes (via `form.processing` ou `router.visit(..., { preserveState: false })`).

---

## 5. Auth et session

- **Sanctum en mode SPA** :
  - Utiliser `EnsureFrontendRequestsAreStateful` dans le groupe de middleware qui gère les routes du frontend (souvent le groupe `web`).
  - Les requêtes depuis le même domaine (même origine) envoient les cookies de session et le CSRF ; Sanctum considère la requête comme « stateful » et utilise la session pour l’auth.
- **Flux SSO externe** :
  - Garder le flux actuel : appel API externe → vérification token → sync/création user local + `external_token` si besoin.
  - Après validation, connecter l’utilisateur en session Laravel (`Auth::login($user)`) pour que les requêtes Inertia suivantes soient authentifiées.
  - Appliquer `external.sso` sur les routes qui doivent vérifier le token externe (ex. certaines routes `/external/...`).
- **Inactivité** :
  - Conserver le middleware `check.inactivity` (expiration après 30 min).
  - En cas d’expiration : retourner une réponse 401 ou redirection vers la page de login avec un message « Session expirée ». Côté Inertia, gérer la redirection (Inertia gère les redirects Laravel) ou intercepter 401 pour forcer une visite vers `/login`.

---

## 6. API existante à conserver

- **Simulations publiques** : `POST /public/calculate`, `GET/POST /public/simulations/{id}/confirm` → garder en API (JSON) pour usage public ou widget.
- **Attestations publiques** : `GET /public/attestations` (ou équivalent) → garder en API.
- **Références** : garder les endpoints (ex. `GET /api/vehicle-brands`, `/api/vehicle-models`) si d’autres systèmes ou le frontend les appellent en AJAX ; sinon, les fournir uniquement via les props Inertia.
- **Endpoints externes** : `/external/...` gardés pour les appels des autres systèmes ; ne pas les remplacer par des pages Inertia sauf si ces flux deviennent internes au SPA.

Tout le reste (CRUD clients, véhicules, contrats, bordereaux, productions, reporting, paramètres) peut être remplacé par des **routes web + Inertia**.

---

## 7. Fichiers et ressources

- **Uploads** : continuer à utiliser `Storage` (disque local ou S3). Pour les logos courtier, images compagnies, etc., enregistrer les chemins en BDD et générer des URLs via `Storage::url()` ou un contrôleur qui stream le fichier (ex. `GET /storage/companies/{id}/logo`).
- **Assets** : `public/logo.png`, `public/companies/...` accessibles par URL ; en Inertia, utiliser `<img src="/logo.png">` ou les URLs retournées par l’API/backend. En fullstack même domaine, pas de changement particulier.

---

## 8. Base de données

- **Aucune modification** des migrations ni du schéma.
- Garder les seeders existants.
- Dans les contrôleurs Inertia, réutiliser les **relations** et **scopes** (ex. `accessibleBy`, `useFilters`) comme dans l’API pour garantir la cohérence des données et des droits.

---

## 9. Bonnes pratiques

- **Conventions** : respecter les noms de contrôleurs Laravel (ex. `ClientController`), noms de pages Inertia (ex. `Clients/Index.vue`), noms de routes (`clients.index`, etc.).
- **Logique métier** : dans Actions et Services ; les contrôleurs restent fins (validation → appel Action/Service → rendu Inertia).
- **Validation** : FormRequests pour toutes les requêtes avec entrées utilisateur.
- **Gestion d’erreurs** : messages flash (success/error) + erreurs de validation sur les champs ; éventuellement page d’erreur 500/404 Inertia.
- **Tests** : adapter les tests pour couvrir les nouvelles routes web (appels GET/POST vers les URLs Inertia, assertion sur la réponse Inertia ou sur la redirection).

---

## Stack cible

- **Laravel 12**
- **Inertia.js** (côté Laravel : `inertiajs/inertia-laravel`)
- **Vue 3** (ou React) avec **Vite**
- **Sanctum** en mode SPA (cookies, session, `EnsureFrontendRequestsAreStateful`)

---

## Fichiers d’exemple

Dans `docs/examples/` vous trouverez :

- **routes-web-inertia.example.php** : squelette des routes web (auth, dashboard, clients, véhicules, contrats, bordereaux, productions, reporting, comptabilité, paramètres).
- **ClientController-Inertia.example.php** : contrôleur Inertia pour les clients (index, create, store, show, edit, update, destroy) avec réutilisation d’Actions et FormRequests.
- **HandleInertiaRequests-Sanctum.example.php** : middleware Inertia avec partage de `auth.user` et `flash`.
- **sanctum-spa-config.example.md** : rappels pour Sanctum en mode SPA (stateful domains, session).
- **Pages-Clients-Index.vue.example** : page Vue 3 (liste clients avec filtres et pagination). À copier dans `resources/js/Pages/Clients/Index.vue`.

Adaptez les noms d’actions, de routes et les props (ex. HashId pour les URLs) selon votre projet.

---

## Checklist rapide

- [ ] Inertia Laravel + driver Vue 3 (ou React) installé
- [ ] Vite configuré (entrée, alias, plugins)
- [ ] Middleware HandleInertiaRequests enregistré et `share()` rempli
- [ ] Layout Blade unique avec `@inertia` et Vite
- [ ] Sanctum stateful configuré pour le domaine du frontend
- [ ] Routes web créées (auth, dashboard, clients, véhicules, contrats, bordereaux, productions, reporting, comptabilité, paramètres)
- [ ] Contrôleurs Inertia créés, réutilisant Actions/Services/FormRequests
- [ ] Pages Inertia créées (layouts + pages par ressource)
- [ ] Middleware `check.inactivity` appliqué sur les routes protégées
- [ ] Flux SSO externe adapté (session après validation)
- [ ] API publique et externe conservée
- [ ] Uploads et URLs d’assets vérifiés
- [ ] Tests mis à jour pour les routes web
