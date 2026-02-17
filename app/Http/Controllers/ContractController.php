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
use Illuminate\Support\Collection;

class ContractController extends Controller
{
    /**
     * Compagnies pour le formulaire contrat : uniquement celles des rattachements actifs (ASACI).
     * Si pas de token ou API indisponible, retourne toutes les compagnies actives.
     */
    private function companiesForContractForm(Request $request): Collection
    {
        $user = $request->user();
        $token = $user?->external_token;
        if (! $token || ($user->external_token_expires_at && $user->external_token_expires_at->isPast())) {
            return Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        try {
            $data = app(ExternalService::class)->getRelationships($token);
            if (isset($data['errors'])) {
                return Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            }
            $inner = $data['data'] ?? [];
            $list = $inner['data'] ?? (is_array($data['data'] ?? null) ? $data['data'] : []);
            if (! is_array($list)) {
                $list = [];
            }
            $codes = [];
            foreach ($list as $r) {
                if (! empty($r['is_disabled'])) {
                    continue;
                }
                $owner = $r['owner'] ?? null;
                $code = $owner['code'] ?? null;
                if ($code !== null && $code !== '') {
                    $codes[] = $code;
                }
            }
            if ($codes === []) {
                return Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            }
            $filtered = Company::where('is_active', true)->whereIn('code', array_unique($codes))->orderBy('name')->get(['id', 'name']);
            return $filtered->isNotEmpty() ? $filtered : Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        } catch (\Throwable $e) {
            return Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $with = ['client:id,full_name', 'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'company:id,name'];
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

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $contracts = $query->latest()->paginate($perPage)->withQueryString();

        $draftCount = Contract::accessibleBy($user)->where('status', Contract::STATUS_DRAFT)->count();

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search', 'status', 'per_page', 'date_from', 'date_to']),
            'draft_count' => $draftCount,
        ]);
    }

    /**
     * Export des contrats en CSV (Excel), en appliquant les mêmes filtres que la liste.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $with = ['client:id,full_name', 'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'company:id,name'];
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
        $filename = 'contrats-' . now()->format('Y-m-d-His') . '.csv';

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
        $companies = $this->companiesForContractForm($request);

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

        $hasAttestation = $contract->attestation_issued_at !== null || $contract->attestation_number !== null;
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

        $filename = 'contrat-' . ($contract->reference ?? $contract->id) . '.pdf';
        return Pdf::loadView('contracts.pdf', ['contract' => $contract])
            ->stream($filename);
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
        $companies = $this->companiesForContractForm($request);

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
     * Marquer qu'une attestation a été générée pour ce contrat (actif).
     */
    public function markAttestationIssued(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        if ($contract->status !== Contract::STATUS_ACTIVE) {
            return redirect()->route('contracts.show', $contract)->with('error', 'Seul un contrat actif peut avoir une attestation.');
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

        if ($contract->attestation_issued_at !== null || $contract->attestation_number !== null) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Une attestation a déjà été générée pour ce contrat.');
        }

        $codeDemandeur = $request->user()?->external_username;
        $result = $externalService->createProduction($contract, $codeDemandeur);

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
