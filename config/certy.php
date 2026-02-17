<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Certy IA - Assistant intelligent
    |--------------------------------------------------------------------------
    |
    | Nom de la couche IA et activation au niveau application.
    | CERTY_IA_ENABLED=false désactive l'assistant pour toute la plateforme.
    | L'organisation peut ensuite activer/désactiver via Paramètres.
    |
    */

    'name' => env('CERTY_IA_NAME', 'Certy IA'),

    /** Activer la fonctionnalité au niveau application (visible dans la nav, page dédiée). */
    'enabled' => (bool) env('CERTY_IA_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Fournisseur LLM : ollama (local), grok (xAI, gratuit/crédits), openai
    |--------------------------------------------------------------------------
    | ollama = tests locaux. grok = xAI (crédits gratuits possibles, API compatible OpenAI).
    | openai = OpenAI. Si non défini : ollama si OLLAMA_URL, sinon grok si XAI_API_KEY, sinon openai.
    */

    'provider' => env('CERTY_IA_PROVIDER') ?: (
        env('OLLAMA_URL') && ! env('OPENAI_API_KEY') && ! env('XAI_API_KEY') ? 'ollama' :
        (env('XAI_API_KEY') ? 'grok' : 'openai')
    ),

    /** Ollama (tests locaux) */
    'ollama_url' => rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/'),
    'ollama_model' => env('OLLAMA_MODEL', 'llama3.2'),

    /** Grok / xAI — API compatible OpenAI (console.x.ai). Modèles : grok-4-1-fast-non-reasoning, grok-4-1-fast-reasoning, grok-3-mini, grok-3 */
    'grok_api_key' => env('XAI_API_KEY'),
    'grok_model' => env('CERTY_IA_GROK_MODEL', 'grok-4-1-fast-non-reasoning'),

    /** OpenAI */
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('CERTY_IA_MODEL', 'gpt-4o-mini'),
];
