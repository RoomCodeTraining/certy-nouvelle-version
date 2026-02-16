<?php

namespace App\Http\Controllers\Web;

use App\Actions\CreateClientAction;
use App\Actions\UpdateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Exemple de contrôleur Inertia pour les Clients.
 * Réutilise les Actions et FormRequests existants.
 * Adapter le binding {client} si vous utilisez HashId (résolution manuelle ou route model binding personnalisé).
 */
class ClientController extends Controller
{
    public function __construct(
        private CreateClientAction $createClient,
        private UpdateClientAction $updateClient
    ) {}

    public function index(Request $request): Response
    {
        $query = Client::query()
            ->accessibleBy($request->user())
            ->useFilters($request->only(['search', 'type_assure']))
            ->withCount('vehicles')
            ->latest();

        $clients = $query->paginate(15)->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'type_assure']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Clients/Create', [
            'professions' => \App\Models\Profession::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->createClient->execute(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client créé.');
    }

    public function show(Request $request, Client $client): Response|RedirectResponse
    {
        $this->authorize('view', $client);

        $client->load(['vehicles', 'contracts.company']);

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Request $request, Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'professions' => \App\Models\Profession::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $this->updateClient->execute($client, $request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client supprimé.');
    }
}
