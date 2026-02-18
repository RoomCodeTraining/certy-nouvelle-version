<?php

namespace App\Http\Controllers;

use App\Models\Bordereau;
use App\Models\Company;
use App\Models\Contract;
use App\Models\OrganizationCompanyConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BordereauController extends Controller
{
    /**
     * Liste des bordereaux : root = tous, non-root = ceux de son organisation.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->currentOrganization();
        if (! $user->isRoot() && ! $organization) {
            return Inertia::location(route('dashboard'));
        }

        $query = Bordereau::query()
            ->with('company:id,name,code')
            ->orderByDesc('period_end')
            ->orderByDesc('period_start');
        if (! $user->isRoot()) {
            $query->where('organization_id', $organization->id);
        }

        if ($request->filled('search')) {
            $query->where('reference', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('date_from')) {
            $query->where('period_end', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('period_start', '<=', $request->date_to);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $bordereaux = $query->paginate($perPage)->withQueryString()->through(fn (Bordereau $b) => [
            'id' => $b->id,
            'reference' => $b->reference,
            'company' => $b->company ? ['id' => $b->company->id, 'name' => $b->company->name, 'code' => $b->company->code] : null,
            'period_start' => $b->period_start?->format('Y-m-d'),
            'period_end' => $b->period_end?->format('Y-m-d'),
            'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
            'total_commission' => $b->total_commission !== null ? (float) $b->total_commission : null,
            'commission_pct' => $b->commission_pct !== null ? (float) $b->commission_pct : null,
            'status' => $b->status,
        ]);

        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'logo_path'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'logo_url' => $c->logo_path ? asset($c->logo_path) : null])
            ->values()
            ->all();

        return Inertia::render('Bordereaux/Index', [
            'bordereaux' => $bordereaux,
            'companies' => $companies,
            'filters' => $request->only(['search', 'company_id', 'date_from', 'date_to', 'per_page']),
        ]);
    }

    /**
     * Formulaire de création : choix compagnie + période (du -> au).
     */
    public function create(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();
        if (! $organization) {
            return Inertia::location(route('dashboard'));
        }

        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'logo_path'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'logo_url' => $c->logo_path ? asset($c->logo_path) : null])
            ->values()
            ->all();

        return Inertia::render('Bordereaux/Create', [
            'companies' => $companies,
        ]);
    }

    /**
     * Enregistrement d'un bordereau (compagnie + période).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->currentOrganization();
        if (! $organization) {
            return redirect()->route('dashboard')->with('error', 'Organisation non trouvée.');
        }

        $validated = Validator::make($request->all(), [
            'company_id' => ['required', 'exists:companies,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ], [
            'company_id.required' => 'La compagnie est obligatoire.',
            'company_id.exists' => 'La compagnie sélectionnée n\'existe pas.',
            'period_start.required' => 'La date de début de période est obligatoire.',
            'period_end.required' => 'La date de fin de période est obligatoire.',
            'period_end.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ])->validate();

        $periodStart = $validated['period_start'];
        $periodEnd = $validated['period_end'];
        $companyId = $validated['company_id'];

        // Période du/au = date de création des contrats (created_at)
        $contracts = Contract::accessibleBy($user)
            ->where('company_id', $companyId)
            ->whereDate('created_at', '>=', $periodStart)
            ->whereDate('created_at', '<=', $periodEnd)
            ->get();

        $totalAmount = (int) $contracts->sum('total_amount');

        // Commission courtier : config par code compagnie (organization_company_configs.code = companies.code)
        $company = Company::find($companyId);
        $commissionPct = null;
        if ($company && $company->code) {
            $config = OrganizationCompanyConfig::where('code', $company->code)->first();
            if ($config && $config->commission !== null) {
                $commissionPct = (float) $config->commission;
            }
        }
        $totalCommission = 0;
        foreach ($contracts as $c) {
            $amt = (int) ($c->total_amount ?? 0);
            if ($commissionPct !== null && $amt > 0) {
                $totalCommission += round($amt * ($commissionPct / 100), 0);
            }
        }

        $bordereau = Bordereau::create([
            'organization_id' => $organization->id,
            'company_id' => $companyId,
            'reference' => null,
            'status' => Bordereau::STATUS_DRAFT,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
            'commission_pct' => $commissionPct,
        ]);
        $bordereau->update(['reference' => Bordereau::generateUniqueReference()]);

        return redirect()->route('bordereaux.show', $bordereau)
            ->with('success', 'Bordereau créé pour la période sélectionnée.');
    }

    /**
     * Détail d'un bordereau (contrats de la période pour la compagnie).
     */
    public function show(Request $request, Bordereau $bordereau): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isRoot()) {
            $organization = $user->currentOrganization();
            if (! $organization || $bordereau->organization_id !== $organization->id) {
                return redirect()->route('bordereaux.index')->with('error', 'Bordereau non trouvé.');
            }
        }

        $bordereau->load('company:id,name,code');

        // Période du/au = date de création des contrats (created_at)
        $contracts = Contract::accessibleBy($user)
            ->where('company_id', $bordereau->company_id)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name', 'vehicle:id,registration_number', 'vehicle.brand:id,name', 'vehicle.model:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Contract $c) => [
                'id' => $c->id,
                'start_date' => $c->start_date?->format('Y-m-d'),
                'end_date' => $c->end_date?->format('Y-m-d'),
                'client' => $c->client?->full_name ?? '—',
                'vehicle' => $c->vehicle
                    ? trim(($c->vehicle->brand?->name ?? '').' '.($c->vehicle->model?->name ?? '').' '.($c->vehicle->registration_number ?? '')) ?: ($c->vehicle->registration_number ?? '—')
                    : '—',
                'total_amount' => $c->total_amount,
                'status' => $c->status,
                'attestation_number' => $c->attestation_number,
            ]);

        $isDraft = $bordereau->status === Bordereau::STATUS_DRAFT;

        return Inertia::render('Bordereaux/Show', [
            'bordereau' => [
                'id' => $bordereau->id,
                'reference' => $bordereau->reference,
                'company' => $bordereau->company ? ['id' => $bordereau->company->id, 'name' => $bordereau->company->name, 'code' => $bordereau->company->code] : null,
                'period_start' => $bordereau->period_start?->format('Y-m-d'),
                'period_end' => $bordereau->period_end?->format('Y-m-d'),
                'total_amount' => $bordereau->total_amount !== null ? (float) $bordereau->total_amount : null,
                'total_commission' => $bordereau->total_commission !== null ? (float) $bordereau->total_commission : null,
                'commission_pct' => $bordereau->commission_pct !== null ? (float) $bordereau->commission_pct : null,
                'status' => $bordereau->status,
            ],
            'contracts' => $contracts,
            'can_validate' => $isDraft,
            'can_delete' => $isDraft,
        ]);
    }

    /**
     * Valider le bordereau (brouillon → validé). Une fois validé, plus aucune opération possible.
     */
    public function validate(Request $request, Bordereau $bordereau): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isRoot()) {
            $organization = $user->currentOrganization();
            if (! $organization || $bordereau->organization_id !== $organization->id) {
                return redirect()->route('bordereaux.index')->with('error', 'Bordereau non trouvé.');
            }
        }
        if ($bordereau->status !== Bordereau::STATUS_DRAFT) {
            return redirect()->route('bordereaux.show', $bordereau)->with('error', 'Seul un bordereau en brouillon peut être validé.');
        }
        $bordereau->update(['status' => Bordereau::STATUS_VALIDATED]);
        return redirect()->route('bordereaux.show', $bordereau)->with('success', 'Bordereau validé.');
    }

    /**
     * Supprimer le bordereau (uniquement en brouillon).
     */
    public function destroy(Request $request, Bordereau $bordereau): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isRoot()) {
            $organization = $user->currentOrganization();
            if (! $organization || $bordereau->organization_id !== $organization->id) {
                return redirect()->route('bordereaux.index')->with('error', 'Bordereau non trouvé.');
            }
        }
        if ($bordereau->status !== Bordereau::STATUS_DRAFT) {
            return redirect()->route('bordereaux.show', $bordereau)->with('error', 'Seul un bordereau en brouillon peut être supprimé.');
        }
        $bordereau->delete();
        return redirect()->route('bordereaux.index')->with('success', 'Bordereau supprimé.');
    }

    /**
     * Export PDF du bordereau.
     */
    public function pdf(Request $request, Bordereau $bordereau): HttpResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isRoot()) {
            $organization = $user->currentOrganization();
            if (! $organization || $bordereau->organization_id !== $organization->id) {
                return redirect()->route('bordereaux.index')->with('error', 'Bordereau non trouvé.');
            }
        }
        $bordereau->load('company');
        // Période du/au = date de création des contrats (created_at)
        $contracts = Contract::query()
            ->where('organization_id', $bordereau->organization_id)
            ->where('company_id', $bordereau->company_id)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name', 'vehicle:id,registration_number', 'vehicle.brand:id,name', 'vehicle.model:id,name'])
            ->orderBy('created_at')
            ->get();
        $filename = 'bordereau-' . $bordereau->reference . '.pdf';
        return Pdf::loadView('bordereaux.pdf', [
            'bordereau' => $bordereau,
            'contracts' => $contracts,
        ])->stream($filename);
    }

    /**
     * Export Excel (CSV) du bordereau.
     */
    public function excel(Request $request, Bordereau $bordereau): StreamedResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isRoot()) {
            $organization = $user->currentOrganization();
            if (! $organization || $bordereau->organization_id !== $organization->id) {
                return redirect()->route('bordereaux.index')->with('error', 'Bordereau non trouvé.');
            }
        }
        $bordereau->load('company');
        // Période du/au = date de création des contrats (created_at)
        $contracts = Contract::query()
            ->where('organization_id', $bordereau->organization_id)
            ->where('company_id', $bordereau->company_id)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name', 'vehicle:id,registration_number', 'vehicle.brand:id,name', 'vehicle.model:id,name'])
            ->orderBy('created_at')
            ->get();

        $filename = 'bordereau-' . $bordereau->reference . '.csv';

        return new StreamedResponse(function () use ($bordereau, $contracts) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, ['Référence', 'Compagnie', 'Période du', 'Période au', 'Montant total', 'Commission %', 'Commission (F CFA)', 'Statut'], ';');
            fputcsv($out, [
                $bordereau->reference,
                $bordereau->company?->name ?? '',
                $bordereau->period_start?->format('d/m/Y') ?? '',
                $bordereau->period_end?->format('d/m/Y') ?? '',
                $bordereau->total_amount ?? 0,
                $bordereau->commission_pct !== null ? (string) $bordereau->commission_pct : '',
                $bordereau->total_commission ?? 0,
                $bordereau->status,
            ], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['Date début', 'Date fin', 'Client', 'Véhicule', 'Montant', 'N° attestation'], ';');
            foreach ($contracts as $c) {
                $vehicle = $c->vehicle
                    ? trim(($c->vehicle->brand?->name ?? '') . ' ' . ($c->vehicle->model?->name ?? '') . ' ' . ($c->vehicle->registration_number ?? '')) ?: ($c->vehicle->registration_number ?? '')
                    : '';
                fputcsv($out, [
                    $c->start_date?->format('d/m/Y') ?? '',
                    $c->end_date?->format('d/m/Y') ?? '',
                    $c->client?->full_name ?? '',
                    $vehicle,
                    $c->total_amount ?? 0,
                    $c->attestation_number ?? '',
                ], ';');
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
