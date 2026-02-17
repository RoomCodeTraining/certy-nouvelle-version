<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertyIaSettingsController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $organization = $request->user()->currentOrganization();
        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Settings/CertyIa', [
            'certyIaName' => config('certy.name', 'Certy IA'),
            'certyIaEnabledAtApp' => config('certy.enabled', false),
            'certyIaEnabled' => $organization->certy_ia_enabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $request->user()->currentOrganization();
        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            abort(403);
        }

        $request->validate([
            'certy_ia_enabled' => 'required|boolean',
        ]);

        $organization->update([
            'certy_ia_enabled' => $request->boolean('certy_ia_enabled'),
        ]);

        return back()->with('success', 'Paramètre enregistré.');
    }
}
