<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OptionalGuarantee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OptionalGuaranteeController extends Controller
{
    public function index(): Response
    {
        $guarantees = OptionalGuarantee::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('Settings/OptionalGuarantees/Index', [
            'guarantees' => $guarantees,
        ]);
    }

    public function update(Request $request, OptionalGuarantee $guarantee): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
            'base' => ['required', 'in:new,venale'],
            'enabled' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $guarantee->update([
            'label' => $data['label'],
            'rate' => $data['rate'],
            'base' => $data['base'],
            'enabled' => $data['enabled'],
            'sort_order' => $data['sort_order'] ?? $guarantee->sort_order,
        ]);

        return redirect()
            ->route('settings.guarantees.index')
            ->with('success', 'Garanties mises à jour.');
    }
}

