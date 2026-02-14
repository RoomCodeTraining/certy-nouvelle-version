<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (request()->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Index', [
            'employeeCountRanges' => Organization::employeeCountRanges(),
            'referralSources' => Organization::referralSources(),
            'industries' => Organization::industries(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_count_range' => ['required', 'string', 'in:1-10,11-50,51-200,201+'],
            'referral_source' => ['required', 'string', 'in:google,bouche_a_oreille,reseaux_sociaux,recommandation,pub,autre'],
            'industry' => ['required', 'string', 'in:cabinet,ecole,ong,sante,commerce,autre'],
        ]);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $counter = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'employee_count_range' => $validated['employee_count_range'],
            'referral_source' => $validated['referral_source'],
            'industry' => $validated['industry'],
        ]);

        $request->user()->organizations()->attach($organization->id, ['role' => 'admin']);

        $defaultPlan = Plan::default();
        if ($defaultPlan) {
            Subscription::create([
                'organization_id' => $organization->id,
                'plan_id' => $defaultPlan->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);
        }

        $request->user()->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard');
    }
}
