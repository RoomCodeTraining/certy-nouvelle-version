<?php

namespace App\Http\Controllers;

use App\Actions\CreateVehicleAction;
use App\Actions\UpdateVehicleAction;
use App\Http\Requests\QuickStoreVehicleRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\CirculationZone;
use App\Models\Client;
use App\Models\Color;
use App\Models\EnergySource;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleGender;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Vehicle::accessibleBy($user)
            ->with(['client:id,full_name,owner_id', 'client.owner:id,name', 'brand:id,name', 'model:id,name', 'color:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('model', fn ($m) => $m->where('name', 'like', '%'.$search.'%'));
            });
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortOrder = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['created_at', 'reference', 'registration_number', 'updated_at'];
        if (in_array($sortColumn, $allowedSort, true)) {
            $query->orderBy($sortColumn, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $vehicles = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['search', 'per_page', 'sort', 'order']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $clients = Client::accessibleBy($user)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $client = null;
        if ($request->filled('client_id')) {
            $c = Client::accessibleBy($user)->find($request->client_id);
            if ($c) {
                $client = $c->only(['id', 'full_name']);
            }
        }

        return Inertia::render('Vehicles/Create', [
            'clients' => $clients,
            'client' => $client,
            'brands' => VehicleBrand::with('models:id,vehicle_brand_id,name')->get(['id', 'name']),
            'circulationZones' => CirculationZone::orderBy('name')->get(['id', 'name']),
            'energySources' => EnergySource::orderBy('name')->get(['id', 'name']),
            'vehicleUsages' => VehicleUsage::orderBy('name')->get(['id', 'name']),
            'vehicleTypes' => VehicleType::orderBy('name')->get(['id', 'name']),
            'vehicleCategories' => VehicleCategory::orderBy('name')->get(['id', 'name']),
            'vehicleGenders' => VehicleGender::orderBy('name')->get(['id', 'name']),
            'colors' => Color::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreVehicleRequest $request, CreateVehicleAction $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $client = Client::findOrFail($validated['client_id']);
        $this->authorizeClient($request, $client);
        $vehicle = $action->execute($validated);
        if ($request->wantsJson()) {
            $vehicle->load('brand:id,name', 'model:id,name');
            return response()->json(['vehicle' => $vehicle]);
        }
        return redirect()->route('clients.show', $client)->with('success', 'Véhicule ajouté.');
    }

    /**
     * Création rapide véhicule (depuis la page contrat) : champs minimaux, retourne JSON.
     */
    public function quickStore(QuickStoreVehicleRequest $request, CreateVehicleAction $action): JsonResponse
    {
        $validated = $request->validated();
        $client = Client::findOrFail($validated['client_id']);
        $this->authorizeClient($request, $client);
        $vehicle = $action->execute(array_merge($validated, [
            'body_type' => null,
            'circulation_zone_id' => null,
            'energy_source_id' => null,
            'vehicle_usage_id' => null,
            'vehicle_type_id' => null,
            'vehicle_category_id' => null,
            'vehicle_gender_id' => null,
            'color_id' => null,
            'fiscal_power' => null,
            'payload_capacity' => null,
            'engine_capacity' => null,
            'seat_count' => null,
            'year_of_first_registration' => null,
            'first_registration_date' => null,
            'registration_card_number' => null,
            'chassis_number' => null,
            'new_value' => null,
            'replacement_value' => null,
        ]));
        $vehicle->load('brand:id,name', 'model:id,name');
        return response()->json(['vehicle' => $vehicle]);
    }

    public function show(Request $request, Vehicle $vehicle): Response|RedirectResponse
    {
        $this->authorizeVehicle($request, $vehicle);

        $vehicle->load([
            'client', 'brand', 'model',
            'circulationZone:id,name', 'energySource:id,name', 'vehicleUsage:id,name',
            'vehicleType:id,name', 'vehicleCategory:id,name', 'vehicleGender:id,name', 'color:id,name',
            'contracts.company',
        ]);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
        ]);
    }

    public function edit(Request $request, Vehicle $vehicle): Response
    {
        $this->authorizeVehicle($request, $vehicle);
        $vehicle->load('client:id,full_name');

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle,
            'brands' => VehicleBrand::with('models:id,vehicle_brand_id,name')->get(['id', 'name']),
            'circulationZones' => CirculationZone::orderBy('name')->get(['id', 'name']),
            'energySources' => EnergySource::orderBy('name')->get(['id', 'name']),
            'vehicleUsages' => VehicleUsage::orderBy('name')->get(['id', 'name']),
            'vehicleTypes' => VehicleType::orderBy('name')->get(['id', 'name']),
            'vehicleCategories' => VehicleCategory::orderBy('name')->get(['id', 'name']),
            'vehicleGenders' => VehicleGender::orderBy('name')->get(['id', 'name']),
            'colors' => Color::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, UpdateVehicleAction $action): RedirectResponse
    {
        $this->authorizeVehicle($request, $vehicle);
        $action->execute($vehicle, $request->validated());
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Véhicule mis à jour.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($request, $vehicle);
        $clientId = $vehicle->client_id;
        $vehicle->delete();

        return redirect()->route('clients.show', $clientId)->with('success', 'Véhicule supprimé.');
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        if (! Client::accessibleBy($request->user())->where('id', $client->id)->exists()) {
            abort(403);
        }
    }

    private function authorizeVehicle(Request $request, Vehicle $vehicle): void
    {
        if (! Vehicle::accessibleBy($request->user())->where('id', $vehicle->id)->exists()) {
            abort(403);
        }
    }
}
