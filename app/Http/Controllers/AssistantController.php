<?php

namespace App\Http\Controllers;

use App\Models\AssistantMessage;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class AssistantController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->currentOrganization();
        $documentCount = $organization?->documents()->count() ?? 0;

        $messages = $organization
            ? AssistantMessage::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->orderBy('created_at')
                ->get(['role', 'content', 'created_at'])
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => $m->created_at->toIso8601String(),
                ])
                ->values()
            : collect();

        return Inertia::render('Assistant/Index', [
            'documentCount' => $documentCount,
            'messages' => $messages,
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $organization = $user->currentOrganization();

        if (! $organization) {
            return response()->json(['error' => 'Aucune organisation.'], 403);
        }

        $subscriptionService = app(SubscriptionService::class);
        if (! $subscriptionService->canUseAssistant($organization)) {
            return response()->json(['error' => 'Quota d\'appels assistant atteint ce mois. Passez à un plan supérieur.'], 403);
        }

        // Sauvegarder le message utilisateur
        AssistantMessage::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'user',
            'content' => $request->question,
        ]);

        $docs = $organization->documents()
            ->get(['title', 'filename', 'mime_type', 'extracted_text']);

        $contextParts = [];
        foreach ($docs as $d) {
            $line = "— {$d->title} ({$d->filename})";
            if (! empty($d->extracted_text)) {
                $line .= "\n  Contenu extrait :\n" . preg_replace('/\n+/', "\n  ", $d->extracted_text);
            } else {
                $line .= "\n  (contenu non disponible pour ce type de fichier)";
            }
            $contextParts[] = $line;
        }

        $context = $contextParts
            ? "Documents de l'utilisateur (avec contenu extrait si disponible pour PDF/DOCX) :\n\n" . implode("\n\n", $contextParts)
            : "L'utilisateur n'a pas encore de documents importés.";

        $history = AssistantMessage::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get(['role', 'content']);

        $historyText = $history->map(fn ($m) => ($m->role === 'user' ? 'Utilisateur: ' : 'Assistant: ') . $m->content)->implode("\n\n");

        $prompt = <<<PROMPT
Tu es l'assistant des archives intelligentes. Tu aides l'utilisateur à gérer et interroger ses documents.

{$context}

Historique récent de la conversation :
{$historyText}

Tu as accès au contenu extrait des documents PDF et DOCX. Utilise-le pour résumer, citer ou répondre aux questions de l'utilisateur. Réponds de façon concise et utile en français à la dernière question de l'utilisateur.
PROMPT;

        $ollamaUrl = config('services.ollama.url');
        $model = config('services.ollama.model');

        try {
            $response = Http::timeout(120)
                ->post("{$ollamaUrl}/api/generate", [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'error' => 'Impossible de joindre Ollama. Vérifiez qu\'Ollama est démarré (ollama serve) et que OLLAMA_URL est correct dans .env.',
                ], 503);
            }

            $body = $response->json();
            $answer = trim($body['response'] ?? 'Aucune réponse.');

            $subscriptionService->logAiUsage(
                $organization,
                'ollama',
                $model,
                (int) ($body['prompt_eval_count'] ?? 0),
                (int) ($body['eval_count'] ?? 0),
                null
            );

            // Sauvegarder la réponse assistant
            $assistantMessage = AssistantMessage::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'role' => 'assistant',
                'content' => $answer,
            ]);

            return response()->json([
                'answer' => $answer,
                'message' => [
                    'role' => 'assistant',
                    'content' => $answer,
                    'created_at' => $assistantMessage->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'appel à Ollama : ' . $e->getMessage(),
            ], 503);
        }
    }
}
