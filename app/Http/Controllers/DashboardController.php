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
            ->map(fn (Contract $c) => [
                'id' => $c->id,
                'reference' => $c->reference ?? '—',
                'created_at' => $c->created_at?->format('Y-m-d'),
                'client' => $c->client?->full_name ?? '—',
                'vehicle' => $c->vehicle
                    ? implode(', ', array_filter([
                        trim(collect([$c->vehicle->brand?->name, $c->vehicle->model?->name])->filter()->join(' ')),
                        $c->vehicle->registration_number,
                    ])) ?: '—'
                    : '—',
                'total_amount' => $c->total_amount,
                'status' => $c->status,
            ]);

        return Inertia::render('Dashboard', [
            'organization' => $organization,
            'recentContracts' => $recentContracts,
            // Bandeau d'information sur le nouveau rapport de production (uniquement pour le root)
            'showProductionExportHint' => $user->isRoot(),
        ]);
    }
}
