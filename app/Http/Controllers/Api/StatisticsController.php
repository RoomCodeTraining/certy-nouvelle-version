<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * KPIs et données graphiques pour le tableau de bord.
     * Périmètre selon l'utilisateur (accessibleBy).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Montants : exclure brouillons (en attente) et annulés — uniquement validé, actif, expiré
        $revenusTotaux = (int) Contract::accessibleBy($user)
            ->whereIn('status', [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
            ->sum('total_amount');
        $totalContrats = (int) Contract::accessibleBy($user)->count();
        $clients = (int) Client::accessibleBy($user)->count();
        $vehicules = (int) Vehicle::accessibleBy($user)->count();

        $now = Carbon::now();
        $twelveMonthsAgo = $now->copy()->subMonths(11)->startOfMonth();
        $clientIds = Client::accessibleBy($user)->select('id')->pluck('id');

        // Contrats par mois (12 derniers mois) — regroupement en PHP pour compatibilité multi-DB
        $contratsByMonth = Contract::query()
            ->whereIn('client_id', $clientIds)
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->get(['created_at', 'total_amount', 'status'])
            ->groupBy(fn ($c) => $c->created_at?->format('Y-m'));

        $clientsByMonth = Client::accessibleBy($user)
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->get(['created_at'])
            ->groupBy(fn ($c) => $c->created_at?->format('Y-m'));

        $labels = [];
        $contratsData = [];
        $clientsData = [];
        $revenusData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $mois = $date->format('Y-m');
            $labels[] = $date->locale('fr')->translatedFormat('M Y');
            $contratsInMonth = $contratsByMonth->get($mois) ?? collect();
            $contratsData[] = $contratsInMonth->count();
            $clientsData[] = ($clientsByMonth->get($mois) ?? collect())->count();
            $revenusData[] = (int) $contratsInMonth->whereIn('status', [Contract::STATUS_VALIDATED, Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])->sum('total_amount');
        }

        return response()->json([
            'revenus_totaux' => $revenusTotaux,
            'contrats_actifs' => $totalContrats,
            'clients' => $clients,
            'vehicules' => $vehicules,
            'chart_labels' => $labels,
            'chart_contrats_par_mois' => $contratsData,
            'chart_clients_par_mois' => $clientsData,
            'chart_revenus_par_mois' => $revenusData,
        ]);
    }
}
