<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductionController extends Controller
{
    /**
     * Vue Inertia: tableau de production par utilisateur.
     * Basé sur les contrats actifs avec n° d'attestation renseigné.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isRoot()) {
            abort(403);
        }

        [$rows, $start, $end, $users] = $this->buildData($request);

        return Inertia::render('Reports/Production', [
            'rows' => $rows,
            'filters' => [
                'month' => $request->input('month'),
                'user_id' => $request->input('user_id'),
            ],
            'period_label' => $start->translatedFormat('F Y'),
            'period' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ],
            'users' => $users,
        ]);
    }

    /**
     * Export CSV de la production par utilisateur pour la période sélectionnée.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isRoot()) {
            abort(403);
        }

        [$rows, $start, $contracts] = $this->buildData($request, forExport: true);

        $contractTypeLabels = [
            'VP' => 'Véhicule particulier',
            'TPC' => 'Transport pour propre compte',
            'TPM' => 'Transport privé de marchandises',
            'TWO_WHEELER' => 'Deux roues',
        ];

        $filename = 'production-contrats-' . $start->format('Y-m') . '.xlsx';

        return new StreamedResponse(function () use ($rows, $contracts, $contractTypeLabels, $filename) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Production');

            // En-têtes
            $headers = [
                'Utilisateur',
                'Type',
                'Nombre de contrats',
                'Montant total (FCFA)',
                'Montant avant réductions (FCFA)',
                'Total réductions (FCFA)',
                'Réduction moyenne (%)',
            ];

            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($columns[$index] . '1', $header);
            }

            // Données
            $rowIndex = 2;
            foreach ($rows as $row) {
                $typeCode = $row['type'] ?? null;
                $typeLabel = $typeCode ? ($contractTypeLabels[$typeCode] ?? $typeCode) : '';

                $sheet->setCellValue("A{$rowIndex}", $row['user_name']);
                $sheet->setCellValue("B{$rowIndex}", $typeLabel);
                $sheet->setCellValue("C{$rowIndex}", $row['contracts_count']);
                $sheet->setCellValue("D{$rowIndex}", $row['total_amount']);
                $sheet->setCellValue("E{$rowIndex}", $row['total_before_reduction']);
                $sheet->setCellValue("F{$rowIndex}", $row['total_reduction_amount']);
                $sheet->setCellValue("G{$rowIndex}", $row['avg_reduction_pct']);

                $rowIndex++;
            }

            // Auto width colonnes
            foreach ($columns as $columnLetter) {
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            /**
             * Feuille 2 : détail des contrats avec infos et ATD.
             */
            $detailsSheet = $spreadsheet->createSheet();
            $detailsSheet->setTitle('Contrats');

            $detailHeaders = [
                'Utilisateur',
                'Référence contrat',
                'Type',
                'Client',
                'Véhicule',
                'Compagnie',
                'Date début',
                'Date fin',
                'Durée (jours)',
                'Montant total (FCFA)',
                'Montant avant réductions (FCFA)',
                'Total réductions (FCFA)',
                'N° ATD',
                'Date ATD',
            ];

            // En-têtes de la feuille "Contrats"
            $detailsSheet->fromArray($detailHeaders, null, 'A1');

            $detailRows = [];
            foreach ($contracts as $c) {
                /** @var \App\Models\Contract $c */
                $vehicleLabel = $c->vehicle
                    ? trim(
                        ($c->vehicle->brand?->name ?? '') . ' ' .
                        ($c->vehicle->model?->name ?? '') . ' ' .
                        ($c->vehicle->registration_number ?? '')
                    ) ?: ($c->vehicle->registration_number ?? '')
                    : '';

                $typeLabel = $c->contract_type
                    ? ($contractTypeLabels[$c->contract_type] ?? $c->contract_type)
                    : '';

                $durationDays = ($c->start_date && $c->end_date)
                    ? $c->start_date->diffInDays($c->end_date) + 1
                    : null;

                $detailRows[] = [
                    $c->createdBy?->name ?? '',
                    $c->reference ?? '',
                    $typeLabel,
                    $c->client?->full_name ?? '',
                    $vehicleLabel,
                    $c->company?->name ?? '',
                    $c->start_date?->format('d/m/Y') ?? '',
                    $c->end_date?->format('d/m/Y') ?? '',
                    $durationDays,
                    $c->total_amount ?? 0,
                    $c->total_before_reduction ?? 0,
                    $c->total_reduction_amount ?? 0,
                    $c->attestation_number ?? '',
                    $c->attestation_issued_at?->format('d/m/Y H:i') ?? '',
                ];
            }

            if (! empty($detailRows)) {
                $detailsSheet->fromArray($detailRows, null, 'A2');
            }

            // Auto width pour la feuille "Contrats"
            foreach (range('A', 'N') as $columnLetter) {
                $detailsSheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Sortie XLSX
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');

            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        });
    }

    /**
     * Construit les données agrégées par utilisateur pour la période sélectionnée.
     *
     * @return array{0: array<int, array>, 1: \Carbon\Carbon, 2?: \Carbon\Carbon, 3?: array<int, array{id:int,name:string}>}
     */
    private function buildData(Request $request, bool $forExport = false): array
    {
        $month = $request->input('month');
        if ($month) {
            // Format attendu YYYY-MM
            $start = Carbon::parse($month . '-01')->startOfMonth();
        } else {
            $start = Carbon::now()->startOfMonth();
        }
        $end = (clone $start)->endOfMonth();

        $query = Contract::query()
            ->with(['createdBy:id,name'])
            // ->where('status', Contract::STATUS_ACTIVE)
            // ->whereNotNull('attestation_number')
            // Production mensuelle basée sur la date de début de contrat
            ->whereBetween('start_date', [$start, $end]);

        if ($request->filled('user_id')) {
            $query->where('created_by_id', $request->input('user_id'));
        }

        $contracts = $query->get();

        // Pour l'export Excel, on a besoin des relations pour la feuille "Contrats"
        if ($forExport) {
            $contracts->load([
                'client.owner',
                'vehicle.brand',
                'vehicle.model',
                'company',
            ]);
        }

        // Regroupement par (utilisateur, type de contrat)
        $grouped = $contracts->groupBy(function (Contract $c) {
            return ($c->created_by_id ?: 0) . '|' . ($c->contract_type ?? '');
        });

        $rows = $grouped->map(function ($contracts, $key) {
            /** @var \Illuminate\Support\Collection $contracts */
            /** @var \App\Models\Contract $first */
            $first = $contracts->first();
            $creator = $first?->createdBy;
            [$userId, $type] = explode('|', (string) $key, 2);

            $totalContracts = $contracts->count();
            $totalAmount = (int) $contracts->sum('total_amount');
            $totalBefore = (int) $contracts->sum(fn (Contract $c) => $c->total_before_reduction ?? 0);
            $totalReduction = (int) $contracts->sum(fn (Contract $c) => $c->total_reduction_amount ?? 0);
            $avgReductionPct = $totalBefore > 0 ? round($totalReduction / $totalBefore * 100, 2) : 0.0;

            return [
                'user_id' => (int) $userId ?: null,
                'user_name' => $creator?->name ?? '—',
                'type' => $type ?: null,
                'contracts_count' => $totalContracts,
                'total_amount' => $totalAmount,
                'total_before_reduction' => $totalBefore,
                'total_reduction_amount' => $totalReduction,
                'avg_reduction_pct' => $avgReductionPct,
            ];
        })->values()->all();

        if ($forExport) {
            return [$rows, $start, $contracts];
        }

        $users = $contracts->pluck('createdBy')->filter()->unique('id')->values()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
            ];
        })->all();

        return [$rows, $start, $end, $users];
    }
}

