<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        $today = now()->startOfDay();
        $expiryThreshold = $today->copy()->addDays(7);

        $contractsExpiringSoon = Contract::accessibleBy($user)
            ->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_VALIDATED])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $expiryThreshold)
            ->with([
                'client:id,full_name',
                'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id',
                'vehicle.brand:id,name',
                'vehicle.model:id,name',
            ])
            ->orderBy('end_date')
            ->limit(10)
            ->get()
            ->map(fn (Contract $c) => [
                'id' => $c->id,
                'reference' => $c->reference ?? '—',
                'end_date' => $c->end_date?->format('Y-m-d'),
                'client' => $c->client?->full_name ?? '—',
                'vehicle' => $c->vehicle
                    ? implode(', ', array_filter([
                        trim(collect([$c->vehicle->brand?->name, $c->vehicle->model?->name])->filter()->join(' ')),
                        $c->vehicle->registration_number,
                    ])) ?: '—'
                    : '—',
                'status' => $c->status,
            ]);

        $recentContracts = Contract::accessibleBy($user)
            ->with([
                'client:id,full_name',
                'vehicle:id,registration_number,vehicle_brand_id,vehicle_model_id',
                'vehicle.brand:id,name',
                'vehicle.model:id,name',
            ])
            ->latest()
            ->take(15)
            ->get()
            ->map(function (Contract $c) {
                // Même logique que la vue détails (Show.vue)
                $primeNette = (int) ($c->rc_amount ?? 0)
                    + (int) ($c->defence_appeal_amount ?? 0)
                    + (int) ($c->person_transport_amount ?? 0)
                    + (int) ($c->optional_guarantees_amount ?? 0);

                $pctBns = (float) ($c->reduction_bns ?? 0);
                $pctComm = (float) ($c->reduction_on_commission ?? 0);
                $pctProf = (float) ($c->reduction_on_profession_percent ?? 0);
                $amtProf = (int) ($c->reduction_on_profession_amount_stored ?? $c->reduction_on_profession_amount ?? 0);

                $bnsAmt = $pctBns > 0 ? (int) round($primeNette * $pctBns / 100) : 0;
                $commAmt = $pctComm > 0 ? (int) round($primeNette * $pctComm / 100) : 0;
                $profAmt = $pctProf > 0 ? (int) round($primeNette * $pctProf / 100) : $amtProf;

                $montantReduction = $bnsAmt + $commAmt + $profAmt;
                $montantApresReduction = max(0, $primeNette - $montantReduction);

                $primeTtc = $montantApresReduction
                    + (int) ($c->accessory_amount ?? 0)
                    + (int) ($c->taxes_amount ?? 0)
                    + (int) ($c->fga_amount ?? 0)
                    + (int) ($c->cedeao_amount ?? 0);

                return [
                    'id' => $c->id,
                    'reference' => $c->reference ?? '—',
                    'created_at' => $c->created_at?->format('Y-m-d'),
                    'end_date' => $c->end_date?->format('Y-m-d'),
                    'client' => $c->client?->full_name ?? '—',
                    'vehicle' => $c->vehicle
                        ? implode(', ', array_filter([
                            trim(collect([$c->vehicle->brand?->name, $c->vehicle->model?->name])->filter()->join(' ')),
                            $c->vehicle->registration_number,
                        ])) ?: '—'
                        : '—',
                    'prime_nette' => $primeNette > 0 ? $primeNette : null,
                    'prime_ttc' => $primeTtc,
                    // Compat pour anciens affichages utilisant total_amount
                    'total_amount' => $primeTtc,
                    'status' => $c->status,
                ];
            });

        return Inertia::render('Dashboard', [
            'organization' => $organization,
            'recentContracts' => $recentContracts,
            'contractsExpiringSoon' => $contractsExpiringSoon,
            'showProductionExportHint' => $user->isRoot(),
        ]);
    }
}
