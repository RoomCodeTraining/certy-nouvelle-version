<?php

namespace App\Http\Controllers;

use App\Models\Bordereau;
use App\Models\Company;
use App\Models\Contract;
use App\Models\OrganizationCompanyConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $bordereaux = $query->paginate($perPage)->withQueryString()->through(function (Bordereau $b) {
            $total = (float) ($b->total_amount ?? 0);
            $commission = (float) ($b->total_commission ?? 0);
            return [
                'id' => $b->id,
                'reference' => $b->reference,
                'company' => $b->company ? ['id' => $b->company->id, 'name' => $b->company->name, 'code' => $b->company->code] : null,
                'period_start' => $b->period_start?->format('Y-m-d'),
                'period_end' => $b->period_end?->format('Y-m-d'),
                'total_amount' => $total > 0 ? $total : null,
                'total_commission' => $b->total_commission !== null ? (float) $b->total_commission : null,
                'commission_pct' => $b->commission_pct !== null ? (float) $b->commission_pct : null,
                'prime_a_reverser' => $total > 0 ? $total - $commission : null,
                'status' => $b->status,
            ];
        });

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

        // Période du/au = date de création des contrats (created_at). Contrats annulés exclus.
        $contracts = Contract::accessibleBy($user)
            ->where('company_id', $companyId)
            ->where('status', '!=', Contract::STATUS_CANCELLED)
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
        // Commission = Taux × Prime nette (FCFA). Prime à reverser = Prime TTC - Commission.
        $totalCommission = 0;
        foreach ($contracts as $c) {
            $primeNette = (int) ($c->prime_nette_for_commission ?? 0);
            if ($commissionPct !== null && $primeNette > 0) {
                $totalCommission += (int) round($primeNette * ($commissionPct / 100), 0);
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

        $commissionPct = $bordereau->commission_pct !== null ? (float) $bordereau->commission_pct : null;

        // Période du/au = date de création des contrats (created_at). Contrats annulés exclus.
        $contracts = Contract::accessibleBy($user)
            ->where('company_id', $bordereau->company_id)
            ->where('status', '!=', Contract::STATUS_CANCELLED)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name,email,phone,address', 'vehicle:id,registration_number,registration_card_number,seat_count,pricing_type,vehicle_brand_id,vehicle_model_id,energy_source_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'vehicle.energySource:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(function (Contract $c, int $index) use ($commissionPct, $bordereau) {
                $primeNette = (int) ($c->prime_nette_for_commission ?? 0);
                $primeTtc = (int) ($c->total_amount ?? 0);
                $commission = $commissionPct !== null && $primeNette > 0
                    ? (int) round($primeNette * ($commissionPct / 100))
                    : 0;
                $primeAReverser = max(0, $primeTtc - $commission);
                $accessory = (int) ($c->accessory_amount ?? 0);
                $taxe = (int) ($c->taxes_amount ?? 0);

                return [
                    'id' => $c->id,
                    'no' => $index + 1,
                    'attestation_number' => $c->attestation_number ?? '—',
                    'policy_insured' => $c->policy_number ?? $c->reference ?? '—',
                    'nom_assure' => $c->client?->full_name ?? '—',
                    'adresse_assure' => $c->client?->address ?? '—',
                    'telephone_assure' => $c->client?->phone ?? '—',
                    'email_assure' => $c->client?->email ?? '—',
                    'numero_permis' => '—',
                    'date_effet' => $c->start_date?->format('Y-m-d'),
                    'date_echeance' => $c->end_date?->format('Y-m-d'),
                    'carte_grise' => $c->vehicle?->registration_card_number ?? '—',
                    'marque' => $c->vehicle?->brand?->name ?? '—',
                    'modele' => $c->vehicle?->model?->name ?? '—',
                    'type_vehicule' => match ($c->contract_type ?? $c->vehicle?->pricing_type ?? '') {
                    'VP' => 'VP',
                    'TPC' => 'TPC',
                    'TPM' => 'TPM',
                    'TWO_WHEELER' => '2 roues',
                    default => $c->contract_type ?? $c->vehicle?->pricing_type ?? '—',
                },
                    'energie' => $c->vehicle?->energySource?->name ?? '—',
                    'immat' => $c->vehicle?->registration_number ?? '—',
                    'nbre_pl' => $c->vehicle?->seat_count ?? '—',
                    'prime_nette' => $primeNette,
                    'accessoire' => $accessory,
                    'taxe' => $taxe,
                    'prime_ttc' => $primeTtc,
                    'taux_comm' => $commissionPct,
                    'commission' => $commission,
                    'prime_a_reverser' => $primeAReverser,
                    'montant_encaisse' => $primeAReverser,
                    'status' => $c->status,
                    'status_label' => match ($c->status ?? '') {
                        'draft' => 'Brouillon',
                        'validated' => 'Validé',
                        'active' => 'Actif',
                        'cancelled' => 'Annulé',
                        'expired' => 'Expiré',
                        default => $c->status ?? '—',
                    },
                    'paid' => $bordereau->status === Bordereau::STATUS_PAID ? 'Oui' : 'Non',
                    'date_paiement' => $bordereau->paid_at?->format('d/m/Y') ?? '—',
                    'montant_restant' => 0,
                ];
            });

        $isDraft = $bordereau->status === Bordereau::STATUS_DRAFT;
        $total = (float) ($bordereau->total_amount ?? 0);
        $commission = (float) ($bordereau->total_commission ?? 0);

        return Inertia::render('Bordereaux/Show', [
            'bordereau' => [
                'id' => $bordereau->id,
                'reference' => $bordereau->reference,
                'company' => $bordereau->company ? ['id' => $bordereau->company->id, 'name' => $bordereau->company->name, 'code' => $bordereau->company->code] : null,
                'period_start' => $bordereau->period_start?->format('Y-m-d'),
                'period_end' => $bordereau->period_end?->format('Y-m-d'),
                'total_amount' => $total > 0 ? $total : null,
                'total_commission' => $bordereau->total_commission !== null ? (float) $bordereau->total_commission : null,
                'commission_pct' => $bordereau->commission_pct !== null ? (float) $bordereau->commission_pct : null,
                'prime_a_reverser' => $total > 0 ? $total - $commission : null,
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
        // Période du/au = date de création des contrats (created_at). Contrats annulés exclus.
        $contracts = Contract::query()
            ->where('organization_id', $bordereau->organization_id)
            ->where('company_id', $bordereau->company_id)
            ->where('status', '!=', Contract::STATUS_CANCELLED)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name,email,phone,address', 'vehicle:id,registration_number,registration_card_number,seat_count,pricing_type,vehicle_brand_id,vehicle_model_id,energy_source_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'vehicle.energySource:id,name'])
            ->orderBy('created_at')
            ->get();
        $filename = 'bordereau-' . $bordereau->reference . '.pdf';
        return Pdf::loadView('bordereaux.pdf', [
            'bordereau' => $bordereau,
            'contracts' => $contracts,
        ])->stream($filename);
    }

    /**
     * Export Excel du bordereau (même colonnes que le PDF, style propre).
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
        $bordereau->load(['company', 'organization:id,name']);
        $commissionPct = $bordereau->commission_pct !== null ? (float) $bordereau->commission_pct : null;
        $contracts = Contract::accessibleBy($user)
            ->where('organization_id', $bordereau->organization_id)
            ->where('company_id', $bordereau->company_id)
            ->where('status', '!=', Contract::STATUS_CANCELLED)
            ->whereDate('created_at', '>=', $bordereau->period_start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $bordereau->period_end->format('Y-m-d'))
            ->with(['client:id,full_name,email,phone,address', 'vehicle:id,registration_number,registration_card_number,seat_count,pricing_type,vehicle_brand_id,vehicle_model_id,energy_source_id', 'vehicle.brand:id,name', 'vehicle.model:id,name', 'vehicle.energySource:id,name'])
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bordereau');

        $companyName = $bordereau->company?->name ?? '—';
        $companyCode = $bordereau->company?->code ? ' (' . $bordereau->company->code . ')' : '';
        $courtier = $bordereau->organization?->name ?? '—';
        $period = ($bordereau->period_start?->format('d/m/Y') ?? '') . ' → ' . ($bordereau->period_end?->format('d/m/Y') ?? '');
        $statut = match ($bordereau->status ?? '') {
            'draft' => 'Brouillon', 'validated' => 'Validé', 'submitted' => 'Soumis', 'approved' => 'Approuvé',
            'rejected' => 'Rejeté', 'paid' => 'Payé', default => $bordereau->status ?? '—',
        };
        $primeTtc = (float) ($bordereau->total_amount ?? 0);
        $commission = (float) ($bordereau->total_commission ?? 0);
        $primeAReverser = (int) ($primeTtc - $commission);

        $row = 1;
        $sheet->setCellValue('A' . $row, 'BORDEREAU DE PRODUCTION ET DE REVERSEMENT DE PRIMES');
        $sheet->setCellValue('Y' . $row, 'BORD. N° ' . ($bordereau->reference ?? ''));
        $sheet->mergeCells('A1:X1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Y1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('Y1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row = 3;

        $sheet->setCellValue('A' . $row, 'Référence');
        $sheet->setCellValue('B' . $row, $bordereau->reference ?? '—');
        $row++;
        $sheet->setCellValue('A' . $row, 'Compagnie');
        $sheet->setCellValue('B' . $row, $companyName . $companyCode);
        $row++;
        $sheet->setCellValue('A' . $row, 'Courtier');
        $sheet->setCellValue('B' . $row, $courtier);
        $row++;
        $sheet->setCellValue('A' . $row, 'Période');
        $sheet->setCellValue('B' . $row, $period);
        $row++;
        $sheet->setCellValue('A' . $row, 'Statut');
        $sheet->setCellValue('B' . $row, $statut);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Prime TTC (F CFA)');
        $sheet->setCellValue('B' . $row, number_format($primeTtc, 0, ',', ' '));
        $row++;
        $sheet->setCellValue('A' . $row, 'Commission courtier (F CFA)');
        $sheet->setCellValue('B' . $row, number_format($commission, 0, ',', ' '));
        if ($commissionPct !== null) {
            $sheet->setCellValue('C' . $row, '(' . number_format($commissionPct, 1, ',', ' ') . ' %)');
        }
        $row++;
        $sheet->setCellValue('A' . $row, 'Prime à reverser (F CFA)');
        $sheet->setCellValue('B' . $row, number_format($primeAReverser, 0, ',', ' '));
        $row += 2;

        $headers = ['N°', 'N° attestation', 'Police/Assuré', 'Nom assuré', 'Adresse', 'Tél', 'Email', 'Date effet', 'Date échéance', 'N° carte grise', 'Marque', 'Modèle', 'Type', 'Énergie', 'Immat', 'Pl', 'Prime nette', 'Access.', 'Taxe', 'Prime TTC', 'Taux %', 'Commission', 'Primes à reverser', 'Montant encaissé'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $col++;
        }
        $headerRange = 'A' . $row . ':' . chr(ord('A') + count($headers) - 1) . $row;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
        $dataStartRow = $row + 1;

        $sumPrimeNette = $sumAccessory = $sumTaxe = $sumPrimeTtc = $sumCommission = $sumPrimeAReverser = 0;
        foreach ($contracts as $i => $c) {
            $row++;
            $primeNette = (int) ($c->prime_nette_for_commission ?? 0);
            $primeTtcC = (int) ($c->total_amount ?? 0);
            $comm = $commissionPct !== null && $primeNette > 0 ? (int) round($primeNette * ($commissionPct / 100)) : 0;
            $prReverser = max(0, $primeTtcC - $comm);
            $accessory = (int) ($c->accessory_amount ?? 0);
            $taxe = (int) ($c->taxes_amount ?? 0);
            $sumPrimeNette += $primeNette;
            $sumAccessory += $accessory;
            $sumTaxe += $taxe;
            $sumPrimeTtc += $primeTtcC;
            $sumCommission += $comm;
            $sumPrimeAReverser += $prReverser;

            $typeVal = match ($c->contract_type ?? $c->vehicle?->pricing_type ?? '') { 'VP' => 'VP', 'TPC' => 'TPC', 'TPM' => 'TPM', 'TWO_WHEELER' => '2 roues', default => $c->contract_type ?? $c->vehicle?->pricing_type ?? '—' };

            $rowData = [
                $i + 1,
                $c->attestation_number ?? '—',
                $c->policy_number ?? $c->reference ?? '—',
                $c->client?->full_name ?? '—',
                $c->client?->address ?? '—',
                $c->client?->phone ?? '—',
                $c->client?->email ?? '—',
                $c->start_date?->format('d/m/Y') ?? '—',
                $c->end_date?->format('d/m/Y') ?? '—',
                $c->vehicle?->registration_card_number ?? '—',
                $c->vehicle?->brand?->name ?? '—',
                $c->vehicle?->model?->name ?? '—',
                $typeVal,
                $c->vehicle?->energySource?->name ?? '—',
                $c->vehicle?->registration_number ?? '—',
                $c->vehicle?->seat_count ?? '—',
                $primeNette,
                $accessory,
                $taxe,
                $primeTtcC,
                $commissionPct !== null ? number_format($commissionPct, 1, ',', ' ') : '—',
                $comm,
                $prReverser,
                $prReverser,
            ];
            $sheet->fromArray([$rowData], null, 'A' . $row);
        }

        $row++;
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        $sheet->mergeCells('A' . $totalRow . ':P' . $totalRow);
        $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('Q' . $totalRow, $sumPrimeNette);
        $sheet->setCellValue('R' . $totalRow, $sumAccessory);
        $sheet->setCellValue('S' . $totalRow, $sumTaxe);
        $sheet->setCellValue('T' . $totalRow, $sumPrimeTtc);
        $sheet->setCellValue('V' . $totalRow, $sumCommission);
        $sheet->setCellValue('W' . $totalRow, $sumPrimeAReverser);
        $sheet->setCellValue('X' . $totalRow, $sumPrimeAReverser);
        $sheet->getStyle('A' . $totalRow . ':X' . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

                $dataRange = 'A' . $dataStartRow . ':X' . ($totalRow - 1);
        if ($dataStartRow <= $totalRow - 1) {
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);
        }

                foreach (range('A', 'X') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'bordereau-' . $bordereau->reference . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
