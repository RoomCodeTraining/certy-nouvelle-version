<?php

namespace App\Http\Controllers;

use App\Actions\StoreContractAction;
use App\Actions\UpdateContractAction;
use App\Http\Requests\ContractPreviewRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\CirculationZone;
use App\Models\Client;
use App\Models\Color;
use App\Models\Company;
use App\Models\Contract;
use App\Models\EnergySource;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleGender;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use App\Services\ContractPricingService;
use App\Services\ExternalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ContractController extends Controller
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
     * Compagnies disponibles pour les contrats : uniquement celles issues des rattachements actifs
     * (ASACI). Logo venant de l'API relationships (owner.logo_url).
     *
     * @return array<int, array{id: int, name: string, logo_url: string|null}>
     */
    private function companiesForUser(Request $request): array
    {
        $token = $this->token($request);
        if (! $token) {
            return [];
        }

        $data = $this->externalService->getRelationships($token);
        if (isset($data['errors'])) {
            return [];
        }

        $inner = $data['data'] ?? [];
        $list = $inner['data'] ?? (is_array($data['data'] ?? null) ? $data['data'] : []);
        if (! is_array($list)) {
            $list = [];
        }

        // Map code -> name et code -> logo_url depuis les rattachements actifs (owner = compagnie, API fournit logo_url)
        $codeToName = [];
        $codeToLogoUrl = [];
        foreach ($list as $r) {
            if (! is_array($r) || ! empty($r['is_disabled'])) {
                continue;
            }
            $owner = $r['owner'] ?? null;
            if (! is_array($owner)) {
                continue;
            }
            $code = $owner['code'] ?? null;
            $name = $owner['name'] ?? null;
            $logoUrl = $owner['logo_url'] ?? null;
            if (is_string($code) && $code !== '') {
                $codeToName[$code] = is_string($name) && $name !== '' ? $name : $code;
                if (is_string($logoUrl) && $logoUrl !== '') {
                    $codeToLogoUrl[$code] = $logoUrl;
                }
            }
        }
        $codes = array_values(array_unique(array_keys($codeToName)));

        if (empty($codes)) {
            return [];
        }

        // S'assurer que les compagnies existent et sont activées localement.
        foreach ($codes as $code) {
            Company::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $codeToName[$code] ?? $code,
                    'is_active' => true,
                ]
            );
        }

        $companies = Company::whereIn('code', $codes)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Liste pour Inertia avec logo_url venant de l'API relationships.
        $out = [];
        foreach ($companies as $c) {
            $out[] = [
                'id' => $c->id,
                'name' => $c->name,
                'logo_url' => $codeToLogoUrl[$c->code] ?? null,
            ];
        }
        return $out;
    }

    /**
     * Formate les compagnies pour Inertia (id, name, logo_url). Utilisé en édition quand on ajoute la compagnie courante.
     */
    private function formatCompaniesForInertia(iterable $companies, array $logoByCode = []): array
    {
        $out = [];
        foreach ($companies as $c) {
            $code = is_object($c) ? ($c->code ?? null) : null;
            $out[] = [
                'id' => is_object($c) ? $c->id : $c['id'],
                'name' => is_object($c) ? $c->name : $c['name'],
                'logo_url' => $code ? ($logoByCode[$code] ?? null) : (is_object($c) && $c->logo_path ? asset($c->logo_path) : null),
            ];
        }
        return $out;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $with = ['client:id,full_name,owner_id', 'client.owner:id,name', 'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'company:id,name'];
        if (Schema::hasColumn('contracts', 'parent_id')) {
            $with[] = 'parent:id';
        }
        $query = Contract::accessibleBy($user)->with($with);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('contract_type', 'like', '%'.$search.'%')
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('company', fn ($co) => $co->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('registration_number', 'like', '%'.$search.'%'));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('end_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortOrder = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['created_at', 'reference', 'start_date', 'end_date', 'status', 'total_amount'];
        if (in_array($sortColumn, $allowedSort, true)) {
            $query->orderBy($sortColumn, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $contracts = $query->paginate($perPage)->withQueryString();

        $draftCount = Contract::accessibleBy($user)->where('status', Contract::STATUS_DRAFT)->count();

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search', 'status', 'per_page', 'date_from', 'date_to', 'sort', 'order']),
            'draft_count' => $draftCount,
        ]);
    }

    /**
     * Export des contrats en CSV (Excel), en appliquant les mêmes filtres que la liste.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $with = ['client:id,full_name,owner_id', 'client.owner:id,name', 'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'company:id,name'];
        if (Schema::hasColumn('contracts', 'parent_id')) {
            $with[] = 'parent:id';
        }
        if (Schema::hasColumn('contracts', 'created_by_id')) {
            $with[] = 'createdBy:id,name';
            $with[] = 'updatedBy:id,name';
        }
        $query = Contract::accessibleBy($user)->with($with);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('contract_type', 'like', '%'.$search.'%')
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('company', fn ($co) => $co->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('registration_number', 'like', '%'.$search.'%'));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('end_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $contracts = $query->latest()->get();

        $contractTypeLabels = [
            'VP' => 'VP',
            'TPC' => 'Transport pour propre compte',
            'TPM' => 'TPM',
            'TWO_WHEELER' => 'Deux roues',
        ];
        $statusLabels = [
            'draft' => 'Brouillon',
            'validated' => 'Validé',
            'active' => 'Actif',
            'cancelled' => 'Annulé',
            'expired' => 'Expiré',
        ];

        $hasPolicyNumber = Schema::hasColumn('contracts', 'policy_number');
        $hasAttestation = Schema::hasColumn('contracts', 'attestation_issued_at');
        $filename = 'export-contrats-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Date création',
            'Référence',
        ];
        if ($hasPolicyNumber) {
            $headers[] = 'N° police';
        }
        $headers = array_merge($headers, [
            'Affaire',
            'Statut',
            'Client',
            'Propriétaire',
            'Véhicule',
            'Type contrat',
            'Compagnie',
            'Date début',
            'Date fin',
            'Prime de base (FCFA)',
            'RC (FCFA)',
            'Défense recours (FCFA)',
            'Transport de personnes (FCFA)',
            'Accessoires (FCFA)',
            'Taxes (FCFA)',
            'CEDEAO (FCFA)',
            'FGA (FCFA)',
            'Accessoire compagnie (FCFA)',
            'Accessoire agence (FCFA)',
            'Prime TTC (FCFA)',
            'Commission (FCFA)',
            'Réduction BNS (%)',
            'Réduction BNS (FCFA)',
            'Réduction sur commission (%)',
            'Réduction sur commission (FCFA)',
            'Réduction profession (%)',
            'Réduction profession (FCFA)',
            'Réduction (FCFA)',
            'Total réductions (FCFA)',
            'Montant total (FCFA)',
        ]);
        if ($hasAttestation) {
            $headers = array_merge($headers, ['Attestation émise le', 'N° attestation', 'Lien attestation']);
        }
        $hasCreatedBy = Schema::hasColumn('contracts', 'created_by_id');
        if ($hasCreatedBy) {
            $headers = array_merge($headers, ['Créé par', 'Modifié par']);
        }

        return new StreamedResponse(function () use ($contracts, $contractTypeLabels, $statusLabels, $headers, $hasPolicyNumber, $hasAttestation, $hasCreatedBy) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, $headers, ';');

            foreach ($contracts as $c) {
                $vehicleLabel = $c->vehicle
                    ? trim(($c->vehicle->brand?->name ?? '').' '.($c->vehicle->model?->name ?? '').' '.($c->vehicle->registration_number ?? '')) ?: ($c->vehicle->registration_number ?? '')
                    : '';

                $row = [
                    $c->created_at?->format('d/m/Y H:i') ?? '',
                    $c->reference ?? '',
                ];
                if ($hasPolicyNumber) {
                    $row[] = $c->policy_number ?? '';
                }
                $row = array_merge($row, [
                    $c->parent_id ? 'Renouvellement' : 'Nouvelle affaire',
                    $statusLabels[$c->status] ?? $c->status,
                    $c->client?->full_name ?? '',
                    $c->client?->owner?->name ?? '',
                    $vehicleLabel,
                    $contractTypeLabels[$c->contract_type] ?? $c->contract_type,
                    $c->company?->name ?? '',
                    $c->start_date?->format('d/m/Y') ?? '',
                    $c->end_date?->format('d/m/Y') ?? '',
                    (string) ($c->base_amount ?? ''),
                    (string) ($c->rc_amount ?? ''),
                    (string) ($c->defence_appeal_amount ?? ''),
                    (string) ($c->person_transport_amount ?? ''),
                    (string) ($c->accessory_amount ?? ''),
                    (string) ($c->taxes_amount ?? ''),
                    (string) ($c->cedeao_amount ?? ''),
                    (string) ($c->fga_amount ?? ''),
                    (string) ($c->company_accessory ?? ''),
                    (string) ($c->agency_accessory ?? ''),
                    (string) ($c->prime_ttc ?? ''),
                    (string) ($c->commission_amount ?? ''),
                    (string) ($c->reduction_bns ?? ''),
                    (string) ($c->reduction_bns_amount ?? ''),
                    (string) ($c->reduction_on_commission ?? ''),
                    (string) ($c->reduction_on_commission_amount ?? ''),
                    (string) ($c->reduction_on_profession_percent ?? ''),
                    (string) ($c->reduction_on_profession_amount_stored ?? ''),
                    (string) ($c->reduction_amount ?? ''),
                    (string) ($c->total_reduction_amount ?? ''),
                    (string) ($c->total_amount ?? ''),
                ]);
                if ($hasAttestation) {
                    $row[] = $c->attestation_issued_at?->format('d/m/Y H:i') ?? '';
                    $row[] = $c->attestation_number ?? '';
                    $row[] = $c->attestation_link ?? '';
                }
                if ($hasCreatedBy) {
                    $row[] = $c->createdBy?->name ?? '';
                    $row[] = $c->updatedBy?->name ?? '';
                }
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $clients = Client::accessibleBy($user)
            ->with('vehicles:id,client_id,registration_number,pricing_type')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
        $companies = $this->companiesForUser($request);

        $parentContract = null;
        if ($request->filled('parent_id')) {
            $parent = Contract::find($request->parent_id);
            if ($parent && Contract::accessibleBy($user)->where('id', $parent->id)->exists()) {
                $parentContract = $parent->only(['id', 'client_id', 'vehicle_id', 'company_id', 'contract_type', 'end_date']);
                if (! empty($parentContract['end_date'])) {
                    $parentContract['end_date'] = $parent->end_date->format('Y-m-d');
                }
            }
        }

        return Inertia::render('Contracts/Create', [
            'clients' => $clients,
            'companies' => $companies,
            'contractTypes' => $this->contractTypes(),
            'durationOptions' => $this->durationOptions(),
            'parentContract' => $parentContract,
            'typeAssureOptions' => [
                ['value' => Client::TYPE_TAPP, 'label' => 'Personne physique (TAPP)'],
                ['value' => Client::TYPE_TAPM, 'label' => 'Personne morale (TAPM)'],
            ],
            'vehicleBrands' => VehicleBrand::with('models:id,vehicle_brand_id,name')->get(['id', 'name']),
            'circulationZones' => CirculationZone::orderBy('name')->get(['id', 'name']),
            'energySources' => EnergySource::orderBy('name')->get(['id', 'name']),
            'vehicleUsages' => VehicleUsage::orderBy('name')->get(['id', 'name']),
            'vehicleTypes' => VehicleType::orderBy('name')->get(['id', 'name']),
            'vehicleCategories' => VehicleCategory::orderBy('name')->get(['id', 'name']),
            'vehicleGenders' => VehicleGender::orderBy('name')->get(['id', 'name']),
            'colors' => Color::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Redirige vers la création d'un contrat en renouvellement (contrat enfant du contrat donné).
     */
    public function renew(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        return redirect()->route('contracts.create', ['parent_id' => $contract->id]);
    }

    /**
     * Prévisualisation du calcul de prime (appelée en AJAX pour le récap dynamique).
     */
    public function preview(ContractPreviewRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $vehicle = Vehicle::with('energySource')->findOrFail($validated['vehicle_id']);
        if (! Vehicle::accessibleBy($request->user())->where('id', $vehicle->id)->exists()) {
            abort(403);
        }

        $contract = new Contract([
            'vehicle_id' => $vehicle->id,
            'client_id' => $vehicle->client_id,
            'contract_type' => $validated['contract_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);
        $contract->setRelation('vehicle', $vehicle);

        $amounts = app(ContractPricingService::class)->calculate($contract);
        if (empty($amounts)) {
            return response()->json([
                'prime_amount' => null,
                'accessory_amount' => null,
                'total_premium' => null,
                'amounts' => [],
            ]);
        }

        $primeWithoutAccessory = $amounts['base_amount'] + $amounts['rc_amount'] + $amounts['defence_appeal_amount']
            + $amounts['person_transport_amount'] + $amounts['taxes_amount'] + $amounts['cedeao_amount'] + $amounts['fga_amount'];
        $totalPremium = $primeWithoutAccessory + $amounts['accessory_amount'];

        return response()->json([
            'prime_amount' => $primeWithoutAccessory,
            'accessory_amount' => $amounts['accessory_amount'],
            'total_premium' => $totalPremium,
            'amounts' => $amounts,
        ]);
    }

    public function store(StoreContractRequest $request, StoreContractAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $this->authorizeClientVehicle($request, $validated['client_id'], $validated['vehicle_id']);
        $override = $request->input('accessory_amount_override');
        $contract = $action->execute($request->user(), $validated, $override);

        if ($contract->status === Contract::STATUS_VALIDATED) {
            return redirect()->route('contracts.show', $contract)
                ->with('success', 'Contrat validé. Vous pouvez générer l\'attestation digitale ou consulter le contrat en PDF.');
        }
        return redirect()->route('contracts.index')->with('success', 'Contrat enregistré en brouillon.');
    }


    public function show(Request $request, Contract $contract): Response|RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        $relations = ['client', 'vehicle.brand', 'vehicle.model', 'company'];
        if (Schema::hasColumn('contracts', 'parent_id')) {
            $relations[] = 'parent:id,start_date,end_date,status';
            $relations[] = 'children:id,parent_id,start_date,end_date,status,total_amount';
        }
        if (Schema::hasColumn('contracts', 'created_by_id')) {
            $relations[] = 'createdBy:id,name';
            $relations[] = 'updatedBy:id,name';
        }
        $contract->load($relations);

        // Une attestation "réelle" = numéro ou lien disponible (pas seulement attestation_issued_at)
        $hasAttestation = $contract->attestation_number !== null || $contract->attestation_link !== null;
        $canEditAttestation = in_array($contract->status, [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true) && ! $hasAttestation;

        return Inertia::render('Contracts/Show', [
            'contract' => $contract,
            'can_edit_attestation' => $canEditAttestation,
            'has_attestation' => $hasAttestation,
        ]);
    }

    /**
     * Page d'impression / PDF du contrat (ouvre dans un nouvel onglet, l'utilisateur peut "Imprimer > Enregistrer en PDF").
     */
    public function pdf(Request $request, Contract $contract): HttpResponse
    {
        $this->authorizeContract($request, $contract);

        $contract->load([
            'client.profession',
            'vehicle.brand',
            'vehicle.model',
            'vehicle.energySource',
            'vehicle.vehicleUsage',
            'vehicle.circulationZone',
            'company',
        ]);

        $companyLogoBase64 = $this->resolveCompanyLogoForPdf($request, $contract->company);

        $filename = 'contrat-' . ($contract->reference ?? $contract->id) . '.pdf';
        return Pdf::loadView('contracts.pdf', [
            'contract' => $contract,
            'company_logo_base64' => $companyLogoBase64,
        ])->stream($filename);
    }

    /**
     * Récupère le logo de la compagnie depuis l'API relationships (owner.logo_url).
     * Retourne une data URI base64 pour inclusion dans le PDF, ou null si indisponible.
     */
    private function resolveCompanyLogoForPdf(Request $request, ?Company $company): ?string
    {
        if (! $company || ! $company->code) {
            return null;
        }

        $token = $this->token($request);
        if (! $token) {
            return null;
        }

        $data = $this->externalService->getRelationships($token);
        if (isset($data['errors'])) {
            return null;
        }

        $list = $data['data'] ?? [];
        if (! is_array($list)) {
            $list = [];
        }

        $logoUrl = null;
        foreach ($list as $r) {
            if (! is_array($r) || ! empty($r['is_disabled'])) {
                continue;
            }
            $owner = $r['owner'] ?? null;
            if (! is_array($owner)) {
                continue;
            }
            if (($owner['code'] ?? '') === $company->code) {
                $logoUrl = $owner['logo_url'] ?? null;
                if (is_string($logoUrl) && $logoUrl !== '') {
                    break;
                }
            }
        }

        if (! $logoUrl) {
            return null;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 5],
            'ssl' => ['verify_peer' => true],
        ]);
        $imageContent = @file_get_contents($logoUrl, false, $context);
        if ($imageContent === false || $imageContent === '') {
            return null;
        }

        $mime = 'image/png';
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($imageContent);
        if (is_string($detected) && str_starts_with($detected, 'image/')) {
            $mime = $detected;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($imageContent);
    }

    public function edit(Request $request, Contract $contract): Response|RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if (in_array($contract->status, [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true)) {
            return redirect()->route('contracts.show', $contract)->with('error', 'Un contrat validé ne peut pas être modifié.');
        }
        $user = $request->user();
        $clients = Client::accessibleBy($user)
            ->with('vehicles:id,client_id,registration_number')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
        $companies = $this->companiesForUser($request);
        // En édition, on garde la compagnie du contrat même si elle n'est plus rattachée (pour éviter un select vide)
        if ($contract->company_id) {
            $hasCompany = collect($companies)->contains('id', $contract->company_id);
            if (! $hasCompany) {
                $current = Company::find($contract->company_id);
                if ($current) {
                    $companies[] = ['id' => $current->id, 'name' => $current->name, 'logo_url' => null];
                }
            }
        }
        usort($companies, fn ($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract,
            'clients' => $clients,
            'companies' => $companies,
            'contractTypes' => $this->contractTypes(),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract, UpdateContractAction $action): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if (in_array($contract->status, [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true)) {
            return redirect()->route('contracts.show', $contract)->with('error', 'Un contrat validé ne peut pas être modifié.');
        }
        $this->authorizeClientVehicle($request, $request->validated('client_id'), $request->validated('vehicle_id'));
        $action->execute($contract, $request->validated(), $request->user());
        return redirect()->route('contracts.show', $contract)->with('success', 'Contrat mis à jour.');
    }

    /**
     * Valider un contrat en brouillon (passage au statut validé).
     */
    public function validate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if ($contract->status !== Contract::STATUS_DRAFT) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Seul un contrat en brouillon peut être validé.');
        }
        $update = ['status' => Contract::STATUS_VALIDATED];
        if (Schema::hasColumn('contracts', 'updated_by_id')) {
            $update['updated_by_id'] = $request->user()->id;
        }
        $contract->update($update);
        Event::dispatch(new \App\Events\ContractValidated($contract, $request->user()));

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrat validé. Vous pouvez générer l\'attestation ou consulter le contrat en PDF.');
    }

    public function cancel(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if (! in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true)) {
            return redirect()->route('contracts.show', $contract)->with('error', 'Ce contrat ne peut pas être annulé.');
        }
        $update = ['status' => Contract::STATUS_CANCELLED];
        if (Schema::hasColumn('contracts', 'updated_by_id')) {
            $update['updated_by_id'] = $request->user()->id;
        }
        $contract->update($update);

        return redirect()->route('contracts.show', $contract)->with('success', 'Contrat annulé.');
    }

    /**
     * Marquer qu'une attestation a été générée pour ce contrat (validé ou actif).
     */
    public function markAttestationIssued(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if (! in_array($contract->status, [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true)) {
            return redirect()->route('contracts.show', $contract)->with('error', 'Seul un contrat validé ou actif peut avoir une attestation.');
        }
        $contract->update(['attestation_issued_at' => now()]);

        return redirect()->route('contracts.show', $contract)->with('success', 'Attestation marquée comme générée.');
    }

    /**
     * Générer l'attestation digitale via l'API ASACI productions.
     * Réservé aux contrats validés ou actifs n'ayant pas encore d'attestation.
     */
    public function generateAttestation(Request $request, Contract $contract, ExternalService $externalService): RedirectResponse
    {
        $this->authorizeContract($request, $contract);

        if (! in_array($contract->status, [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE], true)) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Seul un contrat validé ou actif peut générer une attestation.');
        }

        // if ($contract->attestation_issued_at !== null || $contract->attestation_number !== null) {
        //     return redirect()->route('contracts.show', $contract)
        //         ->with('error', 'Une attestation a déjà été générée pour ce contrat.');
        // }

        $token = $request->user()?->external_token;
        if (! $token || ($request->user()?->external_token_expires_at && $request->user()->external_token_expires_at->isPast())) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Connexion ASACI requise. Veuillez vous reconnecter à la plateforme digitale.');
        }

        $result = $externalService->createProduction($contract, $token, $request->user());

        if (! ($result['success'] ?? false)) {
            $message = $result['errors'][0]['title'] ?? 'Impossible de générer l\'attestation.';
            return redirect()->route('contracts.show', $contract)->with('error', $message);
        }

        $contract->update([
            'attestation_issued_at' => now(),
            'attestation_number' => $result['numero_attestation'] ?? null,
            'attestation_link' => $result['lien_pdf'] ?? null,
        ]);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Attestation générée avec succès.');
    }

    private function contractTypes(): array
    {
        return [
            ['value' => 'VP', 'label' => 'VP'],
            ['value' => 'TPC', 'label' => 'Transport pour propre compte'],
            ['value' => 'TPM', 'label' => 'TPM'],
            ['value' => 'TWO_WHEELER', 'label' => 'Deux roues'],
        ];
    }

    private function durationOptions(): array
    {
        return [
            ['value' => '1_month', 'label' => 'Mensuel (1 mois)'],
            ['value' => '3_months', 'label' => 'Trimestriel (3 mois)'],
            ['value' => '6_months', 'label' => 'Semestriel (6 mois)'],
            ['value' => '12_months', 'label' => 'Annuel (12 mois)'],
        ];
    }

    private function authorizeContract(Request $request, Contract $contract): void
    {
        if (! Contract::accessibleBy($request->user())->where('id', $contract->id)->exists()) {
            abort(403);
        }
    }

    private function authorizeClientVehicle(Request $request, int $clientId, int $vehicleId): void
    {
        $user = $request->user();
        if (! Client::accessibleBy($user)->where('id', $clientId)->exists()) {
            abort(403);
        }
        $vehicle = Vehicle::where('id', $vehicleId)->where('client_id', $clientId)->firstOrFail();
        if (! Vehicle::accessibleBy($user)->where('id', $vehicle->id)->exists()) {
            abort(403);
        }
    }
}
