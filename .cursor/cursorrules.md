ENVIRONNEMENT

- Le projet Laravel est DÉJÀ créé
- L'environnement de développement utilise DDEV
- AUCUNE commande "php artisan" ne doit être utilisée seule

RÈGLE ABSOLUE COMMANDES

- TOUTES les commandes Artisan doivent commencer par :
  ddev php artisan

EXEMPLES CORRECTS

- ddev php artisan migrate
- ddev php artisan make:model Client
- ddev php artisan make:controller ClientController
- ddev php artisan storage:link

EXEMPLES INTERDITS

- php artisan migrate
- artisan migrate
- php artisan make:\*
- Toute commande Laravel sans le préfixe ddev

COMPORTEMENT DE L'IA

- Si une instruction nécessite une commande Artisan :
    - Toujours utiliser "ddev php artisan"
    - Ne jamais proposer d'alternative
- Si une commande est générée sans le préfixe :
    - Corriger immédiatement
    - S'excuser brièvement
- Ne jamais demander à l'utilisateur s'il utilise DDEV

INSTALLATION & SETUP

- Ne jamais proposer d'installation Laravel
- Ne jamais recréer le projet
- Ne jamais modifier la configuration DDEV
- Ne jamais creer de fichier readme sans mon encore
- Travailler uniquement dans le code existant
- Ne jamais utiliser de box shaddow
- Ne jamais utiliser de gradient

RAPPEL STACK (VERROUILLÉ)

- Laravel + Breeze + Inertia + Vue
- TailwindCSS
- AlpineJS
- Pas de JS vanille
- Pas d'AJAX hors Inertia

INSCRIPTION & ONBOARDING (SAAS MODERNE)

- Flow : Inscription (email, mot de passe) → Onboarding wizard obligatoire → Dashboard
- L'onboarding est un wizard multi-étapes, UX professionnelle et engageante
- Données collectées pendant l'onboarding :
    - Nom de l'organisation / entreprise
    - Nombre d'employés (tranches : 1-10, 11-50, 51-200, 201+)
    - Comment avez-vous connu la plateforme ? (référence : Google, bouche à oreille, réseaux sociaux, recommandation, pub, autre)
    - Domaine / secteur d'activité (cabinet, école, ONG, santé, commerce, autre)
- À la fin de l'onboarding : création de l'organisation + liaison user comme admin
- Système de team/organisation : un user peut appartenir à plusieurs organisations (ou une seule au MVP)
- UX sobre, moderne, typique SaaS B2B (progress indicator, étapes claires, pas de friction)
