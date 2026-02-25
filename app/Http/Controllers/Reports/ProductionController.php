<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        [$rows, $start] = $this->buildData($request, forExport: true);

        $filename = 'production-contrats-' . $start->format('Y-m') . '.csv';

        return new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'Utilisateur',
                'Nombre de contrats',
                'Nb VP',
                'Nb TPC',
                'Nb TPM',
                'Nb Deux roues',
                'Montant total (FCFA)',
                'Montant avant réductions (FCFA)',
                'Total réductions (FCCA)',
                'Réduction moyenne (%)',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['user_name'],
                    $row['contracts_count'],
                    $row['types']['VP'],
                    $row['types']['TPC'],
                    $row['types']['TPM'],
                    $row['types']['TWO_WHEELER'],
                    $row['total_amount'],
                    $row['total_before_reduction'],
                    $row['total_reduction_amount'],
                    $row['avg_reduction_pct'],
                ], ';');
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
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
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereNotNull('attestation_number')
            ->whereBetween('start_date', [$start, $end]);

        if ($request->filled('user_id')) {
            $query->where('created_by_id', $request->input('user_id'));
        }

        $contracts = $query->get();

        $grouped = $contracts->groupBy('created_by_id');

        $rows = $grouped->map(function ($contracts, $userId) {
            /** @var \Illuminate\Support\Collection $contracts */
            /** @var \App\Models\Contract $first */
            $first = $contracts->first();
            $creator = $first?->createdBy;

            $totalContracts = $contracts->count();
            $totalAmount = (int) $contracts->sum('total_amount');
            $totalBefore = (int) $contracts->sum(fn (Contract $c) => $c->total_before_reduction ?? 0);
            $totalReduction = (int) $contracts->sum(fn (Contract $c) => $c->total_reduction_amount ?? 0);
            $avgReductionPct = $totalBefore > 0 ? round($totalReduction / $totalBefore * 100, 2) : 0.0;

            $countVp = $contracts->where('contract_type', Contract::TYPE_VP)->count();
            $countTwo = $contracts->where('contract_type', Contract::TYPE_TWO_WHEELER)->count();
            $countTpc = $contracts->where('contract_type', Contract::TYPE_TPC)->count();
            $countTpm = $contracts->where('contract_type', Contract::TYPE_TPM)->count();

            return [
                'user_id' => $userId,
                'user_name' => $creator?->name ?? '—',
                'contracts_count' => $totalContracts,
                'types' => [
                    'VP' => $countVp,
                    'TPC' => $countTpc,
                    'TPM' => $countTpm,
                    'TWO_WHEELER' => $countTwo,
                ],
                'total_amount' => $totalAmount,
                'total_before_reduction' => $totalBefore,
                'total_reduction_amount' => $totalReduction,
                'avg_reduction_pct' => $avgReductionPct,
            ];
        })->values()->all();

        if ($forExport) {
            return [$rows, $start];
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

