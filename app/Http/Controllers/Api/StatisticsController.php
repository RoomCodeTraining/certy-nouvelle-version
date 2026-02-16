<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * KPIs pour le tableau de bord : revenus totaux, contrats actifs, clients, véhicules.
     * Périmètre selon l'utilisateur (accessibleBy).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $revenusTotaux = (int) Contract::accessibleBy($user)->sum('total_amount');
        $contratsActifs = (int) Contract::accessibleBy($user)->where('status', Contract::STATUS_ACTIVE)->count();
        $clients = (int) Client::accessibleBy($user)->count();
        $vehicules = (int) Vehicle::accessibleBy($user)->count();

        return response()->json([
            'revenus_totaux' => $revenusTotaux,
            'contrats_actifs' => $contratsActifs,
            'clients' => $clients,
            'vehicules' => $vehicules,
        ]);
    }
}
