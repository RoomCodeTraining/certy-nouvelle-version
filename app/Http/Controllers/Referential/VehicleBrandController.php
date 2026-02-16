<?php

namespace App\Http\Controllers\Referential;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VehicleBrandController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $brands = VehicleBrand::withCount('models')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Referential/Brands/Index', [
            'brands' => $brands,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Referential/Brands/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        VehicleBrand::create($validated);

        return redirect()->route('referential.brands.index')->with('success', 'Marque créée.');
    }

    public function edit(VehicleBrand $brand): Response
    {
        return Inertia::render('Referential/Brands/Edit', [
            'brand' => $brand->only(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, VehicleBrand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);
        if (array_key_exists('slug', $validated) && $validated['slug'] === '') {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $brand->update($validated);

        return redirect()->route('referential.brands.index')->with('success', 'Marque mise à jour.');
    }

    public function destroy(VehicleBrand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('referential.brands.index')->with('success', 'Marque supprimée.');
    }
}
