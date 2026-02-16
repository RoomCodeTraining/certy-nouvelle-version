<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationCompanyConfigRequest;
use App\Models\OrganizationCompanyConfig;
use App\Services\ExternalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationCompanyConfigController extends Controller
{
    public function __construct(
        private ExternalService $externalService
    ) {}

    private function token(Request $request): ?string
    {
        $user = $request->user();
        if (! $user || ! $user->external_token) {
            return null;
        }
        if ($user->external_token_expires_at && $user->external_token_expires_at->isPast()) {
            return null;
        }
        return $user->external_token;
    }

    /**
     * Liste des configs (nom, code, commission, identifiant n° police).
     * Modification : admin principal uniquement.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $configs = OrganizationCompanyConfig::orderBy('name')->orderBy('code')->get();
        } catch (\Throwable $e) {
            Log::error('Config index: erreur chargement configs', ['message' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Impossible de charger la configuration. Vérifiez que les migrations sont à jour.');
        }

        $items = $configs->map(fn ($config) => [
            'id' => $config->id,
            'name' => $config->name,
            'code' => $config->code,
            'commission' => $config->commission !== null ? (float) $config->commission : null,
            'policy_number_identifier' => $config->policy_number_identifier,
        ])->all();

        $rattachements = [];
        $token = $this->token($request);
        if ($token) {
            try {
                $data = $this->externalService->getRelationships($token);
                if (! isset($data['errors'])) {
                    $inner = $data['data'] ?? [];
                    $list = $inner['data'] ?? (is_array($data['data'] ?? null) ? $data['data'] : []);
                    foreach (is_array($list) ? $list : [] as $r) {
                        if (! empty($r['is_disabled'])) {
                            continue;
                        }
                        $owner = $r['owner'] ?? null;
                        $rattachements[] = [
                            'id' => $r['id'] ?? null,
                            'owner_name' => $owner['name'] ?? '',
                            'owner_code' => $owner['code'] ?? '',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Config: rattachements non chargés', ['message' => $e->getMessage()]);
            }
        }

        $user = $request->user();

        return Inertia::render('Settings/OrganizationCompanyConfig', [
            'items' => $items,
            'rattachements' => $rattachements,
            'canEdit' => $user ? $user->isRoot() : false,
        ]);
    }

    /**
     * Création ou mise à jour d'une config. Réservé à l'admin principal (isRoot).
     */
    public function update(UpdateOrganizationCompanyConfigRequest $request): RedirectResponse
    {
        Log::info('Config update: request reçu', [
            'all' => $request->all(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        if (! $request->user()->isRoot()) {
            Log::info('Config update: refusé (pas root)');
            return redirect()->route('settings.config')->with('error', 'Seul l\'admin principal peut modifier la configuration.');
        }

        $validated = $request->validated();
        Log::info('Config update: validated', ['validated' => $validated]);

        $attributes = [
            'name' => $validated['name'] ?? null,
            'code' => $validated['code'] ?? null,
            'commission' => isset($validated['commission']) && $validated['commission'] !== null && $validated['commission'] !== ''
                ? $validated['commission']
                : null,
            'policy_number_identifier' => $validated['policy_number_identifier'] ?? null,
        ];

        $config = ! empty($validated['id'])
            ? OrganizationCompanyConfig::find((int) $validated['id'])
            : null;

        if ($config) {
            $config->update($attributes);
            Log::info('Config update: mis à jour', ['id' => $config->id]);
        } else {
            $config = OrganizationCompanyConfig::create($attributes);
            Log::info('Config update: créé', ['id' => $config->id]);
        }

        return back()->with('success', 'Configuration enregistrée.');
    }

    /**
     * Suppression d'une config. Réservé à l'admin principal (isRoot).
     */
    public function destroy(Request $request, OrganizationCompanyConfig $config): RedirectResponse
    {
        if (! $request->user()->isRoot()) {
            Log::info('Config destroy: refusé (pas root)');
            return redirect()->route('settings.config')->with('error', 'Seul l\'admin principal peut supprimer une configuration.');
        }

        $config->delete();
        Log::info('Config destroy: supprimé', ['id' => $config->id]);

        return redirect()->route('settings.config')->with('success', 'Configuration supprimée.');
    }
}
