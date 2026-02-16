<?php

namespace App\Http\Controllers\Referential;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModelController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $models = VehicleModel::with('brand:id,name')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Referential/Models/Index', [
            'models' => $models,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function create(): Response
    {
        $brands = VehicleBrand::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Referential/Models/Create', [
            'brands' => $brands,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        VehicleModel::create($validated);

        return redirect()->route('referential.models.index')->with('success', 'Modèle créé.');
    }

    public function edit(VehicleModel $vehicle_model): Response
    {
        $vehicle_model->load('brand:id,name');
        $brands = VehicleBrand::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Referential/Models/Edit', [
            'model' => $vehicle_model->only(['id', 'vehicle_brand_id', 'name', 'slug', 'brand']),
            'brands' => $brands,
        ]);
    }

    public function update(Request $request, VehicleModel $vehicle_model): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);
        if (array_key_exists('slug', $validated) && $validated['slug'] === '') {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $vehicle_model->update($validated);

        return redirect()->route('referential.models.index')->with('success', 'Modèle mis à jour.');
    }

    public function destroy(VehicleModel $vehicle_model): RedirectResponse
    {
        $vehicle_model->delete();

        return redirect()->route('referential.models.index')->with('success', 'Modèle supprimé.');
    }
}
