<?php

namespace App\Http\Controllers;

use App\Actions\CreateClientAction;
use App\Actions\UpdateClientAction;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Profession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Client::accessibleBy($user)
            ->withCount('vehicles')
            ->with('profession:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('type_assure')) {
            $query->where('type_assure', $request->type_assure);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $clients = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'type_assure', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Clients/Create', [
            'professions' => Profession::orderBy('name')->get(['id', 'name']),
            'typeAssureOptions' => [
                ['value' => Client::TYPE_TAPP, 'label' => 'Personne physique (TAPP)'],
                ['value' => Client::TYPE_TAPM, 'label' => 'Personne morale (TAPM)'],
            ],
        ]);
    }

    public function store(StoreClientRequest $request, CreateClientAction $action): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $client = $action->execute($request->user(), $request->validated());
        if ($request->wantsJson()) {
            return response()->json([
                'client' => array_merge($client->only(['id', 'full_name']), ['vehicles' => []]),
            ]);
        }
        return redirect()->route('clients.index')->with('success', 'Client créé.');
    }

    public function show(Request $request, Client $client): Response|RedirectResponse
    {
        $this->authorizeClient($request, $client);
        $client->load([
            'vehicles.brand',
            'vehicles.model',
            'vehicles.energySource',
            'profession',
            'contracts.company',
            'contracts.vehicle.brand',
            'contracts.vehicle.model',
        ]);

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Request $request, Client $client): Response
    {
        $this->authorizeClient($request, $client);
        $client->load('profession:id,name');

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'professions' => Profession::orderBy('name')->get(['id', 'name']),
            'typeAssureOptions' => [
                ['value' => Client::TYPE_TAPP, 'label' => 'Personne physique (TAPP)'],
                ['value' => Client::TYPE_TAPM, 'label' => 'Personne morale (TAPM)'],
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClientAction $action): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        $action->execute($client, $request->validated());
        return redirect()->route('clients.show', $client)->with('success', 'Client mis à jour.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        if (! Client::accessibleBy($request->user())->where('id', $client->id)->exists()) {
            abort(403);
        }
    }
}
