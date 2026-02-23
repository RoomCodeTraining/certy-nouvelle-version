<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche globale : clients, véhicules, contrats.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([
                'clients' => [],
                'vehicles' => [],
                'contracts' => [],
            ]);
        }

        $term = '%' . $q . '%';

        $clients = Client::accessibleBy($user)
            ->where(function ($query) use ($term) {
                $query->where('full_name', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })
            ->limit(8)
            ->get(['id', 'full_name', 'reference'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->full_name . ($c->reference ? " ({$c->reference})" : ''),
                'href' => route('clients.show', $c->id),
            ]);

        $vehicles = Vehicle::accessibleBy($user)
            ->with('client:id,full_name')
            ->where(function ($query) use ($term) {
                $query->where('registration_number', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', $term))
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $term))
                    ->orWhereHas('model', fn ($m) => $m->where('name', 'like', $term));
            })
            ->limit(8)
            ->get(['id', 'registration_number', 'reference', 'client_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => ($v->registration_number ?? $v->reference ?? '') . ($v->client ? " — {$v->client->full_name}" : ''),
                'href' => route('vehicles.show', $v->id),
            ]);

        $contracts = Contract::accessibleBy($user)
            ->with('client:id,full_name', 'company:id,name')
            ->where(function ($query) use ($term) {
                $query->where('reference', 'like', $term)
                    ->orWhere('contract_type', 'like', $term)
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', $term))
                    ->orWhereHas('company', fn ($co) => $co->where('name', 'like', $term))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('registration_number', 'like', $term));
            })
            ->limit(8)
            ->get(['id', 'reference', 'client_id', 'company_id'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => ($c->reference ?? '') . ($c->client ? " — {$c->client->full_name}" : '') . ($c->company ? " ({$c->company->name})" : ''),
                'href' => route('contracts.show', $c->id),
            ]);

        return response()->json([
            'clients' => $clients,
            'vehicles' => $vehicles,
            'contracts' => $contracts,
        ]);
    }
}
