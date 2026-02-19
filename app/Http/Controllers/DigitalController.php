<?php

namespace App\Http\Controllers;

use App\Services\ExternalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DigitalController extends Controller
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

    private function requireToken(Request $request): string|RedirectResponse
    {
        $token = $this->token($request);
        if (! $token) {
            return redirect()
                ->route('login')
                ->with('error', 'Session expirée ou non connectée au service ASACI. Veuillez vous reconnecter.');
        }
        return $token;
    }

    /**
     * Attestations (certificats) depuis le service externe.
     */
    public function attestations(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $perPage = $request->integer('per_page', 10);
        $data = $this->externalService->getCertificates($token, $request);


        if (isset($data['errors'])) {
            return Inertia::render('Digital/Attestations/Index', [
                'attestations' => [],
                'links' => null,
                'meta' => null,
                'error' => $data['errors'][0]['title'] ?? 'Erreur lors du chargement des attestations.',
                'filters' => $request->only(['per_page', 'printed_at', 'cursor', 'search']),
            ]);
        }

        // Réponse API : { data: [...], links: {...}, meta: { path, per_page, next_cursor, prev_cursor } }
        $list = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        $links = $data['links'] ?? null;
        $meta = $data['meta'] ?? null;

        return Inertia::render('Digital/Attestations/Index', [
            'attestations' => $list,
            'links' => $links,
            'meta' => $meta,
            'error' => null,
            'filters' => $request->only(['per_page', 'printed_at', 'cursor', 'search']),
        ]);
    }

    /**
     * Télécharger le PDF d'une attestation.
     * source=cima : API ASACI (token requis). source=autres : API EATCI BNICB (certificates/related).
     */
    public function downloadAttestation(Request $request, string $reference): HttpResponse|RedirectResponse
    {
        $source = strtolower((string) $request->query('source', ''));

        if ($source === 'autres') {
            $cedeao = $this->externalService->getCertificateRelatedCedeao($reference);
            if ($cedeao === null) {
                return redirect()
                    ->route('digital.attestations')
                    ->with('error', 'Téléchargement indisponible (service non configuré).');
            }
            if (is_array($cedeao) && ($cedeao['ok'] ?? false) === true) {
                $res = $cedeao['response'];
                $contentType = $res->header('Content-Type', '');
                // Si la réponse est du JSON avec une URL de téléchargement directe, rediriger vers elle (ouvre directement).
                if (str_contains($contentType, 'application/json')) {
                    $body = $res->json();
                    $data = $body['data'] ?? $body;
                    $directUrl = $data['download_link'] ?? $data['download_url'] ?? $body['download_link'] ?? $body['download_url'] ?? null;
                    if ($directUrl && is_string($directUrl) && str_starts_with($directUrl, 'http')) {
                        return redirect()->away($directUrl);
                    }
                }
                // Sinon : réponse binaire (PDF), on renvoie le corps.
                return response($res->body(), $res->status())
                    ->header('Content-Type', $res->header('Content-Type') ?: 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="attestation-'.$reference.'.pdf"');
            }
            $message = is_array($cedeao) && isset($cedeao['message']) ? $cedeao['message'] : 'Téléchargement indisponible pour cette source.';
            return redirect()
                ->route('digital.attestations')
                ->with('error', $message);
        }

        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $result = $this->externalService->downloadCertificate($reference, $token);
        if (is_array($result) && isset($result['errors'])) {
            return redirect()
                ->route('digital.attestations')
                ->with('error', $result['errors'][0]['title'] ?? 'Téléchargement impossible.');
        }

        return response($result->body(), $result->status(), $result->headers())
            ->header('Content-Disposition', 'attachment; filename="attestation-'.$reference.'.pdf"');
    }

    /**
     * Visualiser l'attestation dans le modal (PDF en inline).
     * source=cima : API ASACI (token requis). source=autres : API EATCI BNICB (certificates/related).
     */
    public function viewAttestation(Request $request, string $reference): HttpResponse|RedirectResponse
    {
        $source = strtolower((string) $request->query('source', ''));

        if ($source === 'autres') {
            $cedeao = $this->externalService->getCertificateRelatedCedeao($reference);
            if ($cedeao === null) {
                return redirect()
                    ->route('digital.attestations')
                    ->with('error', 'Visualisation indisponible (service non configuré).');
            }
            if (is_array($cedeao) && ($cedeao['ok'] ?? false) === true) {
                $res = $cedeao['response'];
                $body = $res->body();
                $contentType = $res->header('Content-Type') ?? '';

                // Réponse JSON CEDEAO avec printed_certificate (image base64) : afficher en image comme CIMA
                // On détecte le JSON par le contenu (certaines APIs renvoient application/pdf avec du JSON)
                $trimmed = trim($body);
                if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                    $json = json_decode($body, true);
                    if (is_array($json)) {
                        $printedCertificate = $json['data']['printed_certificate'] ?? $json['printed_certificate'] ?? $json['data']['data']['printed_certificate'] ?? null;
                        if (is_string($printedCertificate) && $printedCertificate !== '') {
                            $isDataUrl = str_starts_with($printedCertificate, 'data:image/');
                            $isRawBase64 = ! str_contains($printedCertificate, '<') && (preg_match('/^[A-Za-z0-9+\/=]+$/', $printedCertificate) || $isDataUrl);
                            if ($isDataUrl || $isRawBase64) {
                                $dataUrl = $isDataUrl ? $printedCertificate : 'data:image/jpeg;base64,'.$printedCertificate;
                                $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
                                    .'<style>html,body{margin:0;padding:0;background:#f1f5f9;}img{display:block;max-width:100%;height:auto;margin:0 auto;}</style></head>'
                                    .'<body><img src="'.htmlspecialchars($dataUrl, ENT_QUOTES, 'UTF-8').'" alt="Attestation"></body></html>';
                                return response($html, 200, [
                                    'Content-Type' => 'text/html; charset=utf-8',
                                    'Content-Disposition' => 'inline; filename="attestation-'.$reference.'.html"',
                                ]);
                            }
                        }
                    }
                }

                // PDF ou autre binaire : renvoyer tel quel
                return response($body, 200, [
                    'Content-Type' => $contentType ?: 'application/pdf',
                    'Content-Disposition' => 'inline; filename="attestation-'.$reference.'.pdf"',
                ]);
            }
            $message = is_array($cedeao) && isset($cedeao['message']) ? $cedeao['message'] : 'Visualisation indisponible pour cette source.';
            return redirect()
                ->route('digital.attestations')
                ->with('error', $message);
        }

        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $result = $this->externalService->getCertificatePrintedPdf($reference, $token);
        if (isset($result['errors'])) {
            // Dernier recours : utiliser l'endpoint download pour la visualisation
            $download = $this->externalService->downloadCertificate($reference, $token);
            if (! is_array($download) || ! isset($download['errors'])) {
                return response($download->body(), 200, [
                    'Content-Type' => $download->headers()->get('Content-Type') ?? 'application/pdf',
                    'Content-Disposition' => 'inline; filename="attestation-'.$reference.'.pdf"',
                ]);
            }
            return redirect()
                ->route('digital.attestations')
                ->with('error', $result['errors'][0]['title'] ?? 'Visualisation impossible.');
        }

        $contentType = $result['content_type'] ?? 'application/pdf';
        $body = $result['body'];

        // Pour les images : renvoyer une page HTML qui affiche l'image sans espace blanc à droite
        if (str_starts_with($contentType, 'image/')) {
            $dataUrl = 'data:'.$contentType.';base64,'.base64_encode($body);
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
                .'<style>html,body{margin:0;padding:0;background:#f1f5f9;}img{display:block;max-width:100%;height:auto;margin:0 auto;}</style></head>'
                .'<body><img src="'.htmlspecialchars($dataUrl, ENT_QUOTES, 'UTF-8').'" alt="Attestation"></body></html>';
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'inline; filename="attestation-'.$reference.'.html"',
            ]);
        }

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="attestation-'.$reference.'.pdf"',
        ]);
    }

    /**
     * Rattachements (relationships) depuis le service externe.
     */
    public function rattachements(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $data = $this->externalService->getRelationships($token);


        if (isset($data['errors'])) {
            return Inertia::render('Digital/Rattachements/Index', [
                'rattachements' => [],
                'links' => null,
                'meta' => null,
                'error' => $data['errors'][0]['title'] ?? 'Erreur lors du chargement des rattachements.',
            ]);
        }

        // API returns { success, message, data: { data: [...], links, meta } }
        $inner = $data['data'] ?? [];
        $list = $inner['data'] ?? (is_array($data['data'] ?? null) ? $data['data'] : []);
        $links = $inner['links'] ?? null;
        $meta = $inner['meta'] ?? null;

        return Inertia::render('Digital/Rattachements/Index', [
            'rattachements' => $list,
            'links' => $links,
            'meta' => $meta,
            'error' => null,
        ]);
    }

    /**
     * Stock (usage / statistiques) depuis le service externe.
     */
    public function stock(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $data = $this->externalService->getStockUsage($token);

        $transactions = [];
        if (! isset($data['errors'])) {
            $txResponse = $this->externalService->getTransactions($token);
            $transactions = isset($txResponse['errors']) ? [] : ($txResponse['data'] ?? $txResponse);
            if (! is_array($transactions)) {
                $transactions = [];
            }
        }

        if (isset($data['errors'])) {
            return Inertia::render('Digital/Stock/Index', [
                'stock' => null,
                'transactions' => [],
                'error' => $data['errors'][0]['title'] ?? 'Erreur lors du chargement du stock.',
            ]);
        }


        return Inertia::render('Digital/Stock/Index', [
            'stock' => $data,
            'transactions' => $transactions,
            'error' => null,
        ]);
    }

    /**
     * Utilisateurs (service externe ASACI).
     */
    public function utilisateurs(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $data = $this->externalService->getUsers($token, $request);


        if (isset($data['errors'])) {
            return Inertia::render('Digital/Utilisateurs/Index', [
                'utilisateurs' => [],
                'links' => null,
                'meta' => null,
                'error' => $data['errors'][0]['title'] ?? 'Erreur lors du chargement des utilisateurs.',
            ]);
        }

        $inner = $data['data'] ?? $data;
        $list = isset($inner['data']) && is_array($inner['data']) ? $inner['data'] : (is_array($inner) ? $inner : []);
        $links = $inner['links'] ?? null;
        $meta = $inner['meta'] ?? null;

        return Inertia::render('Digital/Utilisateurs/Index', [
            'utilisateurs' => $list,
            'links' => $links,
            'meta' => $meta,
            'error' => null,
        ]);
    }

    /**
     * Bureaux (offices) depuis le service externe ASACI.
     */
    public function bureaux(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }

        $data = $this->externalService->getOffices($token);


        if (isset($data['errors'])) {
            return Inertia::render('Digital/Bureaux/Index', [
                'bureaux' => [],
                'links' => null,
                'meta' => null,
                'error' => $data['errors'][0]['title'] ?? 'Erreur lors du chargement des bureaux.',
            ]);
        }

        $inner = $data['data'] ?? $data;
        $list = isset($inner['data']) && is_array($inner['data']) ? $inner['data'] : (is_array($inner) ? $inner : []);
        $links = $inner['links'] ?? null;
        $meta = $inner['meta'] ?? null;

        return Inertia::render('Digital/Bureaux/Index', [
            'bureaux' => $list,
            'links' => $links,
            'meta' => $meta,
            'error' => null,
        ]);
    }

    // ---------- Utilisateurs (CRUD + activer/désactiver) ----------

    public function createUtilisateur(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $roles = $this->externalService->getRoles($token);
        $rolesList = isset($roles['errors']) ? [] : ($roles['data'] ?? $roles);
        $offices = $this->externalService->getOffices($token);
        $officesList = isset($offices['errors']) ? [] : (isset($offices['data']) && is_array($offices['data']) ? $offices['data'] : (is_array($offices) ? $offices : []));

        return Inertia::render('Digital/Utilisateurs/Create', [
            'roles' => $rolesList,
            'offices' => $officesList,
        ]);
    }

    public function storeUtilisateur(Request $request): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:12'],
            'role' => ['nullable', 'string'],
            'office_id' => ['nullable', 'string'],
        ]);
        $username = str()->random(16);
        $data = array_filter([
            'username' => $username,
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'telephone' => $validated['telephone'] ?? null,
            'office_id' => $validated['office_id'] ?? null,
            'role' => $validated['role'] ?? null,
            'password' => $validated['password'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $result = $this->externalService->storeUser($data, $token);
        if (isset($result['errors'])) {
            $sanitized = array_diff_key($data, array_flip(['password']));
            Log::warning('Digital create user: API validation error', [
                'request_payload' => $sanitized,
                'api_response' => $result,
            ]);
            $apiErrors = $result['errors'];
            $details = [];
            $validationErrors = [];

            // Format JSON:API : [ { "detail": "msg", "source": { "pointer": "/password" } } ]
            if (is_array($apiErrors) && isset($apiErrors[0]) && is_array($apiErrors[0])) {
                foreach ($apiErrors as $item) {
                    $detail = $item['detail'] ?? $item['title'] ?? null;
                    if (is_array($detail)) {
                        $detail = implode(' ', array_map(fn ($v) => is_array($v) ? implode(' ', $v) : (string) $v, $detail));
                    }
                    if ($detail !== null && $detail !== '') {
                        $details[] = $detail;
                    }
                    $pointer = $item['source']['pointer'] ?? null;
                    if ($pointer !== null && $detail !== null && $detail !== '') {
                        $field = trim((string) $pointer, '/');
                        if (str_contains($field, '/')) {
                            $field = last(explode('/', $field));
                        }
                        $validationErrors[$field] = $detail;
                    }
                }
            }
            // Format type Laravel : { "email": ["msg"], "role": ["msg"] }
            elseif (is_array($apiErrors) && ! isset($apiErrors[0])) {
                foreach ($apiErrors as $field => $messages) {
                    $list = is_array($messages) ? $messages : [$messages];
                    $text = implode(' ', $list);
                    $details[] = $field.' : '.$text;
                    $validationErrors[$field] = is_array($messages) ? ($messages[0] ?? '') : $messages;
                }
            }

            $message = $result['message'] ?? 'Erreur lors de la création.';
            if ($details !== []) {
                $message = $message.'. '.implode(' ', $details);
            }
            return redirect()
                ->route('digital.utilisateurs.create')
                ->with('error', $message)
                ->with('validation_errors', $validationErrors)
                ->withInput();
        }
        return redirect()->route('digital.utilisateurs')->with('success', 'Utilisateur créé.');
    }

    public function editUtilisateur(Request $request, string $id): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $data = $this->externalService->showUser($id, $token);
        if (isset($data['errors'])) {
            return redirect()->route('digital.utilisateurs')->with('error', $data['errors'][0]['title'] ?? 'Utilisateur introuvable.');
        }
        $userData = $data['data'] ?? $data;
        $roles = $this->externalService->getRoles($token);
        $rolesList = isset($roles['errors']) ? [] : ($roles['data'] ?? $roles);
        $offices = $this->externalService->getOffices($token);
        $officesList = isset($offices['errors']) ? [] : (isset($offices['data']) && is_array($offices['data']) ? $offices['data'] : (is_array($offices) ? $offices : []));

        return Inertia::render('Digital/Utilisateurs/Edit', [
            'utilisateur' => $userData,
            'roles' => $rolesList,
            'offices' => $officesList,
        ]);
    }

    public function updateUtilisateur(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'username' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'string'],
            'office_id' => ['nullable', 'string'],
        ]);
        $data = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        $result = $this->externalService->updateUser($id, $token, $data);
        if (isset($result['errors'])) {
            return redirect()->back()->with('error', $result['errors'][0]['title'] ?? 'Erreur lors de la mise à jour.')->withInput();
        }
        return redirect()->route('digital.utilisateurs')->with('success', 'Utilisateur mis à jour.');
    }

    public function enableUtilisateur(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $this->externalService->activateUser($id, $token);
        return redirect()->route('digital.utilisateurs')->with('success', 'Utilisateur activé.');
    }

    public function disableUtilisateur(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $this->externalService->desactivateUser($id, $token);
        return redirect()->route('digital.utilisateurs')->with('success', 'Utilisateur désactivé.');
    }

    // ---------- Bureaux (CRUD + activer/désactiver) ----------

    public function createBureau(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $officeTypes = $this->externalService->getOfficeTypes($token);
        $typesList = isset($officeTypes['errors']) ? [] : ($officeTypes['data'] ?? $officeTypes);

        return Inertia::render('Digital/Bureaux/Create', [
            'officeTypes' => is_array($typesList) ? $typesList : [],
        ]);
    }

    public function storeBureau(Request $request): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'office_type_id' => ['nullable', 'string'],
        ]);
        $data = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        $result = $this->externalService->storeOffice($data, $token);
        if (isset($result['errors'])) {
            return redirect()->route('digital.bureaux.create')->with('error', $result['errors'][0]['title'] ?? 'Erreur lors de la création.')->withInput();
        }
        return redirect()->route('digital.bureaux')->with('success', 'Bureau créé.');
    }

    public function editBureau(Request $request, string $id): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $data = $this->externalService->showOffice($id, $token);
        if (isset($data['errors'])) {
            return redirect()->route('digital.bureaux')->with('error', $data['errors'][0]['title'] ?? 'Bureau introuvable.');
        }
        $officeData = $data['data'] ?? $data;
        $officeTypes = $this->externalService->getOfficeTypes($token);
        $typesList = isset($officeTypes['errors']) ? [] : ($officeTypes['data'] ?? $officeTypes);

        return Inertia::render('Digital/Bureaux/Edit', [
            'bureau' => $officeData,
            'officeTypes' => is_array($typesList) ? $typesList : [],
        ]);
    }

    public function updateBureau(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'office_type_id' => ['nullable', 'string'],
        ]);
        $data = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        $result = $this->externalService->updateOffice($id, $token, $data);
        if (isset($result['errors'])) {
            return redirect()->back()->with('error', $result['errors'][0]['title'] ?? 'Erreur lors de la mise à jour.')->withInput();
        }
        return redirect()->route('digital.bureaux')->with('success', 'Bureau mis à jour.');
    }

    public function enableBureau(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $this->externalService->activateOffice($id, $token);
        return redirect()->route('digital.bureaux')->with('success', 'Bureau activé.');
    }

    public function disableBureau(Request $request, string $id): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $this->externalService->desactivateOffice($id, $token);
        return redirect()->route('digital.bureaux')->with('success', 'Bureau désactivé.');
    }

    // ---------- Transactions (demandes de stock) ----------

    public function createTransaction(Request $request): Response|RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $relationships = $this->externalService->getRelationships($token);
        if (isset($relationships['errors'])) {
            $organizationsList = [];
        } else {
            $inner = $relationships['data'] ?? [];
            $raw = $inner['data'] ?? (is_array($relationships['data'] ?? null) ? $relationships['data'] : []);
            if (! is_array($raw)) {
                $raw = [];
            }
            // Ne garder que les rattachements actifs (is_disabled !== true)
            $organizationsList = array_values(array_filter($raw, fn ($r) => empty($r['is_disabled'])));
        }
        $certTypes = $this->externalService->getCertificatesTypes($token);
        $certTypesList = isset($certTypes['errors']) ? [] : ($certTypes['data'] ?? $certTypes);
        if (! is_array($certTypesList)) {
            $certTypesList = [];
        }

        return Inertia::render('Digital/Stock/Create', [
            'organizations' => $organizationsList,
            'certificateTypes' => $certTypesList,
        ]);
    }

    public function storeTransactionRequest(Request $request): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'certificate_type_id' => ['required', 'string'],
            'organization_id' => ['required', 'string'],
        ]);
        $payload = [
            'organization_id' => $validated['organization_id'],
            'certificate_type_id' => $validated['certificate_type_id'],
            'quantity' => (int) $validated['quantity'],
            'type' => 'deposit',
        ];
        $result = $this->externalService->storeTransaction($payload, $token);
        if (isset($result['errors'])) {
            return redirect()->route('digital.stock.create')->with('error', $result['errors'][0]['title'] ?? 'Erreur lors de la demande de stock.')->withInput();
        }
        return redirect()->route('digital.stock')->with('success', 'Demande de stock envoyée.');
    }

    public function cancelTransaction(Request $request, string $reference): RedirectResponse
    {
        $token = $this->requireToken($request);
        if ($token instanceof RedirectResponse) {
            return $token;
        }
        $this->externalService->cancelTransaction($reference, $token);
        return redirect()->route('digital.stock')->with('success', 'Transaction annulée.');
    }
}
