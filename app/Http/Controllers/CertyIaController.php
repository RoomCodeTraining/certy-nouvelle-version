<?php

namespace App\Http\Controllers;

use App\Services\CertyIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertyIaController extends Controller
{
    public function __construct(
        protected CertyIaService $certyIaService
    ) {}

    /**
     * Page Certy IA : affiche l'interface ou le message "non disponible".
     */
    public function index(Request $request): Response|JsonResponse
    {
        if (! config('certy.enabled')) {
            return Inertia::render('CertyIa/Index', [
                'available' => false,
                'message' => 'Cette solution n\'est pas disponible pour le moment.',
            ]);
        }

        $organization = $request->user()->currentOrganization();
        // Une fois l'agent activé (CERTY_IA_ENABLED), tout fonctionne sauf si l'org a désactivé explicitement
        $enabledForOrg = $organization && ($organization->certy_ia_enabled !== false);

        if (! $enabledForOrg) {
            return Inertia::render('CertyIa/Index', [
                'available' => false,
                'message' => 'Cette solution n\'est pas disponible pour le moment.',
            ]);
        }

        $provider = config('certy.provider', 'openai');
        $model = match ($provider) {
            'ollama' => config('certy.ollama_model', 'llama3.2'),
            'grok' => config('certy.grok_model', 'grok-4-1-fast-non-reasoning'),
            default => config('certy.openai_model', 'gpt-4o-mini'),
        };
        $providerLabel = match ($provider) {
            'ollama' => 'Ollama',
            'grok' => 'Grok (xAI)',
            default => 'OpenAI',
        };

        return Inertia::render('CertyIa/Index', [
            'available' => true,
            'name' => config('certy.name', 'Certy IA'),
            'agentLabel' => $providerLabel . ' · ' . $model,
        ]);
    }

    /**
     * POST API: envoie une question et renvoie la réponse.
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate(['question' => 'required|string|max:4000']);

        if (! config('certy.enabled')) {
            return response()->json(['answer' => 'Cette solution n\'est pas disponible pour le moment.'], 403);
        }

        $organization = $request->user()->currentOrganization();
        if (! $organization || $organization->certy_ia_enabled === false) {
            return response()->json(['answer' => 'Cette solution n\'est pas disponible pour le moment.'], 403);
        }

        $answer = $this->certyIaService->ask($request->user(), $request->input('question'));

        return response()->json(['answer' => $answer]);
    }
}
