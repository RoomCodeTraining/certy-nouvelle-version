<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertyIaService
{
    /**
     * Répond à une question de l'utilisateur en s'appuyant sur les données accessibles (clients, véhicules, contrats, bordereaux).
     * Pas de quota : utilisation illimitée.
     */
    public function ask(User $user, string $question): string
    {
        $organization = $user->currentOrganization();
        if (! $organization) {
            return "Aucune organisation associée. Impossible de répondre.";
        }

        $context = $this->buildContext($user);
        $systemMessage = $this->getSystemPrompt();
        $userMessage = "Contexte des données (uniquement celles accessibles à l'utilisateur) :\n\n" . $context . "\n\n---\n\nQuestion : " . $question;
        $userMessage .= "\n\nRéponds de façon concise et factuelle en t'appuyant uniquement sur le contexte fourni. Si les données ne permettent pas de répondre, dis-le.";

        $provider = config('certy.provider', 'openai');

        if ($provider === 'ollama') {
            return $this->callOllama($organization, $systemMessage, $userMessage);
        }

        if ($provider === 'grok') {
            $apiKey = config('certy.grok_api_key');
            if (! $apiKey) {
                Log::warning('Certy IA: XAI_API_KEY non configuré');
                return "L'assistant n'est pas configuré (clé API xAI manquante). Définissez XAI_API_KEY (console.x.ai).";
            }
            return $this->callGrok($organization, $systemMessage, $userMessage, $apiKey);
        }

        $apiKey = config('certy.openai_api_key');
        if (! $apiKey) {
            Log::warning('Certy IA: OPENAI_API_KEY non configuré');
            return "L'assistant n'est pas configuré (clé API manquante). Contactez l'administrateur.";
        }

        return $this->callOpenAI($organization, $systemMessage, $userMessage, $apiKey);
    }

    protected function callOllama($organization, string $systemMessage, string $userMessage): string
    {
        $url = config('certy.ollama_url', 'http://localhost:11434');
        $model = config('certy.ollama_model', 'llama3.2');

        try {
            $response = Http::timeout(120)
                ->post($url . '/api/chat', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                Log::error('Certy IA Ollama: API error', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->ollamaErrorMessage($url, $model, $response->status(), null);
            }

            $data = $response->json();
            $content = $data['message']['content'] ?? '';

            return trim($content);
        } catch (\Throwable $e) {
            Log::error('Certy IA Ollama: exception', ['message' => $e->getMessage()]);
            return $this->ollamaErrorMessage($url, $model, null, $e->getMessage());
        }
    }

    protected function ollamaErrorMessage(string $url, string $model, ?int $status, ?string $exceptionMessage): string
    {
        $isDocker = str_contains($url, 'host.docker.internal');
        $lines = [
            "Ollama ne répond pas ou le modèle {$model} n'est pas disponible.",
            '',
            "Sur votre machine (hôte), exécutez :",
            "  1. Ouvrez Ollama (ou lancez-le : ollama serve)",
            "  2. Téléchargez le modèle : ollama run {$model}",
            "  3. Vérifiez : curl http://localhost:11434/api/tags",
        ];
        if ($isDocker) {
            $lines[] = '';
            $lines[] = "Avec Docker, l'app contacte le hôte via " . $url . " — assurez-vous qu'Ollama écoute sur 0.0.0.0 ou que le port 11434 est accessible.";
        }
        if ($status === 404 || ($exceptionMessage && str_contains(strtolower($exceptionMessage), 'not found'))) {
            $lines[] = '';
            $lines[] = "Modèle inconnu : essayez 'ollama run llama3.2' ou 'ollama run llama3.1' puis mettez OLLAMA_MODEL=llama3.2 dans .env";
        }
        return implode("\n", $lines);
    }

    /** xAI Grok — API compatible OpenAI (https://api.x.ai/v1), crédits gratuits possibles. */
    protected function callGrok($organization, string $systemMessage, string $userMessage, string $apiKey): string
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.x.ai/v1/chat/completions', [
                    'model' => config('certy.grok_model', 'grok-4-1-fast-non-reasoning'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => 1024,
                ]);

            if (! $response->successful()) {
                $body = $response->body();
                Log::error('Certy IA Grok: API error', ['status' => $response->status(), 'body' => $body]);
                $userMessage = $this->parseGrokErrorMessage($response->status(), $body);
                return $userMessage;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return trim($content);
        } catch (\Throwable $e) {
            Log::error('Certy IA Grok: exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return "Une erreur est survenue (xAI). Vérifiez votre clé API et le modèle (CERTY_IA_GROK_MODEL). Réessayez plus tard.";
        }
    }

    /** Extrait un message lisible depuis la réponse d'erreur xAI. */
    protected function parseGrokErrorMessage(int $status, string $body): string
    {
        $data = json_decode($body, true);
        if (! is_array($data)) {
            return "Une erreur est survenue (xAI). Réessayez plus tard.";
        }
        // xAI renvoie parfois "error" en string, parfois en objet avec "message"
        $error = $data['error'] ?? null;
        $message = is_array($error) ? ($error['message'] ?? $error['code'] ?? null) : $error;
        $message = is_string($message) ? $message : ($data['message'] ?? null);

        if ($status === 401) {
            return "Clé API xAI invalide ou expirée. Vérifiez XAI_API_KEY (console.x.ai).";
        }
        if ($status === 403) {
            if ($message && (str_contains(strtolower($message), 'credit') || str_contains(strtolower($message), 'permission'))) {
                return "Votre compte xAI n'a pas de crédits ou n'a pas la permission d'utiliser l'API. Ajoutez des crédits sur console.x.ai (Billing / équipe).";
            }
            return "Accès refusé (xAI). " . (is_string($message) ? $message : 'Vérifiez les crédits et permissions sur console.x.ai.');
        }
        if ($status === 404 || ($message && str_contains(strtolower($message), 'model not found'))) {
            return "Modèle xAI introuvable. Dans .env mettez CERTY_IA_GROK_MODEL=grok-4-1-fast-non-reasoning ou grok-3-mini, puis : php artisan config:clear";
        }
        if ($status === 429) {
            return "Limite de requêtes xAI atteinte. Réessayez dans un moment.";
        }
        if (is_string($message) && $message !== '') {
            return "xAI : " . $message;
        }
        return "Une erreur est survenue (xAI). Réessayez plus tard.";
    }

    protected function callOpenAI($organization, string $systemMessage, string $userMessage, string $apiKey): string
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('certy.openai_model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => 1024,
                ]);

            if (! $response->successful()) {
                Log::error('Certy IA: API error', ['status' => $response->status(), 'body' => $response->body()]);
                return "Une erreur est survenue lors de l'appel à l'assistant. Réessayez plus tard.";
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return trim($content);
        } catch (\Throwable $e) {
            Log::error('Certy IA: exception', ['message' => $e->getMessage()]);
            return "Une erreur est survenue. Réessayez plus tard.";
        }
    }

    protected function getSystemPrompt(): string
    {
        return "Tu es l'assistant Certy IA pour une application de gestion de courtage d'assurance (clients, véhicules, contrats, bordereaux). "
            . "Tu réponds en français, de façon concise et professionnelle. "
            . "Tu ne donnes des informations que sur la base du contexte fourni (données accessibles à l'utilisateur). "
            . "Pour les montants, utilise le format F CFA quand c'est pertinent.";
    }

    protected function buildContext(User $user): string
    {
        $org = $user->currentOrganization();
        if (! $org) {
            return 'Aucune donnée.';
        }

        $parts = [];

        $clients = Client::accessibleBy($user)
            ->with('profession:id,name')
            ->get(['id', 'reference', 'full_name', 'email', 'phone', 'type_assure', 'profession_id']);
        $parts[] = "## Clients (" . $clients->count() . ")\n" . $this->formatClients($clients);

        $vehicles = Vehicle::accessibleBy($user)
            ->with(['brand:id,name', 'model:id,name', 'client:id,full_name'])
            ->get();
        $parts[] = "## Véhicules (" . $vehicles->count() . ")\n" . $this->formatVehicles($vehicles);

        $contracts = Contract::accessibleBy($user)
            ->with(['client:id,full_name', 'vehicle:id,registration_number'])
            ->get(['id', 'reference', 'client_id', 'vehicle_id', 'status', 'total_amount', 'start_date', 'end_date', 'contract_type']);
        $parts[] = "## Contrats (" . $contracts->count() . ")\n" . $this->formatContracts($contracts);

        if ($user->isRoot()) {
            $bordereaux = Bordereau::where('organization_id', $org->id)
                ->orderByDesc('period_end')
                ->limit(50)
                ->get(['id', 'reference', 'status', 'period_start', 'period_end', 'total_amount', 'total_commission']);
            $parts[] = "## Bordereaux (" . $bordereaux->count() . ")\n" . $this->formatBordereaux($bordereaux);
        }

        return implode("\n\n", $parts);
    }

    protected function formatClients($clients): string
    {
        if ($clients->isEmpty()) {
            return 'Aucun client.';
        }
        $lines = $clients->take(200)->map(function ($c) {
            $pro = $c->profession?->name ?? '—';
            return "- {$c->reference} | {$c->full_name} | {$c->type_assure} | {$c->email} | {$c->phone} | Profession: {$pro}";
        });
        return $lines->implode("\n");
    }

    protected function formatVehicles($vehicles): string
    {
        if ($vehicles->isEmpty()) {
            return 'Aucun véhicule.';
        }
        $lines = $vehicles->take(200)->map(function ($v) {
            $brand = $v->brand?->name ?? '—';
            $model = $v->model?->name ?? '—';
            $client = $v->client?->full_name ?? '—';
            return "- {$v->reference} | {$brand} {$model} | Immat: {$v->registration_number} | Client: {$client}";
        });
        return $lines->implode("\n");
    }

    protected function formatContracts($contracts): string
    {
        if ($contracts->isEmpty()) {
            return 'Aucun contrat.';
        }
        $lines = $contracts->take(200)->map(function ($c) {
            $client = $c->client?->full_name ?? '—';
            $vehicle = $c->vehicle?->registration_number ?? '—';
            $amount = $c->total_amount !== null ? number_format($c->total_amount, 0, ',', ' ') . ' F CFA' : '—';
            $start = $c->start_date?->format('d/m/Y') ?? '—';
            $end = $c->end_date?->format('d/m/Y') ?? '—';
            return "- {$c->reference} | Client: {$client} | Véhicule: {$vehicle} | {$c->status} | {$c->contract_type} | Montant: {$amount} | {$start} → {$end}";
        });
        return $lines->implode("\n");
    }

    protected function formatBordereaux($bordereaux): string
    {
        if ($bordereaux->isEmpty()) {
            return 'Aucun bordereau.';
        }
        $lines = $bordereaux->map(function ($b) {
            $start = $b->period_start?->format('d/m/Y') ?? '—';
            $end = $b->period_end?->format('d/m/Y') ?? '—';
            $amount = $b->total_amount !== null ? number_format((float) $b->total_amount, 0, ',', ' ') . ' F CFA' : '—';
            $comm = $b->total_commission !== null ? number_format((float) $b->total_commission, 0, ',', ' ') . ' F CFA' : '—';
            return "- {$b->reference} | {$b->status} | Période: {$start} → {$end} | Total: {$amount} | Commission: {$comm}";
        });
        return $lines->implode("\n");
    }
}
