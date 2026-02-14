<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function edit(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();
        $subscriptionService = app(SubscriptionService::class);

        $currentSubscription = null;
        $plans = Plan::orderBy('price_monthly')->get(['id', 'name', 'slug', 'price_monthly', 'limits_documents', 'limits_assistant_calls_per_month', 'features']);

        if ($organization) {
            $sub = $subscriptionService->getActiveSubscription($organization);
            if ($sub) {
                $currentSubscription = [
                    'plan_name' => $sub->plan->name,
                    'plan_slug' => $sub->plan->slug,
                    'expires_at' => $sub->expires_at?->toIso8601String(),
                    'documents_remaining' => $subscriptionService->getDocumentsRemaining($organization),
                    'documents_limit' => $sub->plan->limits_documents,
                    'assistant_calls_remaining' => $subscriptionService->getAssistantCallsRemaining($organization),
                    'assistant_calls_limit' => $sub->plan->limits_assistant_calls_per_month,
                ];
            }
        }

        return Inertia::render('Settings/Subscription', [
            'currentSubscription' => $currentSubscription,
            'plans' => $plans->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price_monthly' => $p->price_monthly,
                'price_formatted' => $p->price_monthly === 0 ? 'Gratuit' : number_format($p->price_monthly, 0, ',', ' ') . ' XOF/mois',
                'limits_documents' => $p->limits_documents,
                'limits_assistant_calls_per_month' => $p->limits_assistant_calls_per_month,
                'features' => $p->features ?? [],
            ]),
            'organizationSlug' => $organization?->slug,
            'billingEmail' => config('services.billing.email', 'billing@example.com'),
        ]);
    }
}
