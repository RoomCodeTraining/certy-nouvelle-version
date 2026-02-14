<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $organization = $request->user()->currentOrganization();

        if (! $organization) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Settings/Organization', [
            'organization' => $organization->only(['id', 'name', 'slug', 'employee_count_range', 'referral_source', 'industry']),
            'industries' => Organization::industries(),
            'employeeCountRanges' => Organization::employeeCountRanges(),
            'referralSources' => Organization::referralSources(),
        ]);
    }

    public function update(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization->update(['name' => $request->name]);

        return back();
    }
}
