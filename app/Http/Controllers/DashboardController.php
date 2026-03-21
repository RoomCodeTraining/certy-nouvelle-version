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
                // Chaque réduction s'applique à chaque garantie → prime nette = somme des montants réduits
                $primeNette = $c->getPrimeNetteForDisplay();
                $primeTtc = (int) ($c->total_amount ?? $c->prime_ttc ?? 0);

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
                    'prime_ttc' => $primeTtc > 0 ? $primeTtc : null,
                    'total_amount' => $primeTtc > 0 ? $primeTtc : null,
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
