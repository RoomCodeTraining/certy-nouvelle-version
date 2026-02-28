<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Logo
    |--------------------------------------------------------------------------
    |
    | Path to the logo image relative to the public directory (e.g. logo.png).
    | Change APP_LOGO in .env or replace the file in public/ to update the logo
    | without editing code.
    |
    */

    'logo' => env('APP_LOGO', 'logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Theme Colors (paramétrable via .env)
    |--------------------------------------------------------------------------
    |
    | Couleurs de la marque : primaire (bleu), secondaire (rouge), accent (vert citron).
    | Valeurs par défaut : Bleu #1e40af, Rouge #dc2626, Vert citron #84cc16.
    |
    */

    'theme' => [
        'primary' => env('APP_THEME_PRIMARY', '#1e40af'),
        'secondary' => env('APP_THEME_SECONDARY', '#dc2626'),
        'accent' => env('APP_THEME_ACCENT', '#84cc16'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contract PDF footer (paramétrable via .env)
    |--------------------------------------------------------------------------
    |
    | Texte affiché en bas de page sur les attestations / contrats PDF.
    | Deux lignes configurables (CONTRACT_FOOTER_LINE_1, CONTRACT_FOOTER_LINE_2).
    |
    */

    'contract_footer' => [
        'line1' => env('CONTRACT_FOOTER_LINE_1', 'SARL au Capital de 1 000 000 FCFA, Entreprise régie par le code CIMA'),
        'line2' => env('CONTRACT_FOOTER_LINE_2', 'Siège social, Tél : (+225) 07 07 89 59 43 / 07 07 05 87 81'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'fr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request max execution time (seconds)
    |--------------------------------------------------------------------------
    | Limite le temps d'exécution des requêtes HTTP. 5 = max 5s par requête.
    | Mettre à 0 pour désactiver (comportement PHP par défaut).
    */
    'request_max_seconds' => (int) env('REQUEST_MAX_SECONDS', 5),

    'asaci_core_url' => env('ASACI_CORE_URL', ''),
    'asaci_office_code' => env('ASACI_OFFICE_CODE', ''),
    'asaci_productions_url' => env('ASACI_PRODUCTIONS_URL', 'https://ppsurceatci.asacitech.com'),
    'asaci_code_demandeur' => env('ASACI_CODE_DEMANDEUR', ''),
    'asaci_code_intermediaire' => env('ASACI_CODE_INTERMEDIAIRE', ''),
    'asaci_code_nature_attestation' => env('ASACI_CODE_NATURE_ATTESTATION', 'JAUN'),

    /*
    | API CEDEAO (EATCI BNICB) pour télécharger / visualiser les attestations.
    | GET {eatci_cedeao_api_url}/api/v1/certificates/related/{reference}
    */
    'eatci_cedeao_api_url' => rtrim(env('EATCI_CEDEAO_API_URL', 'https://eatci-api.bnicb.com'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Admin email
    |--------------------------------------------------------------------------
    | Email de l'admin pour les envois (ex: export Reporting par email).
    */
    'admin_email' => env('ADMIN_EMAIL', 'dsieroger@gmail.com'),

];
