<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $subscription = null;
        if ($request->user()?->currentOrganization()) {
            $org = $request->user()->currentOrganization();
            $svc = app(SubscriptionService::class);
            $sub = $svc->getActiveSubscription($org);
            if ($sub) {
                $subscription = [
                    'plan_name' => $sub->plan->name,
                    'plan_slug' => $sub->plan->slug,
                    'documents_remaining' => $svc->getDocumentsRemaining($org),
                    'documents_limit' => $sub->plan->limits_documents,
                    'assistant_calls_remaining' => $svc->getAssistantCallsRemaining($org),
                    'assistant_calls_limit' => $sub->plan->limits_assistant_calls_per_month,
                ];
            }
        }

        return [
            ...parent::share($request),
            'flash' => [
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'onboarding_completed' => $request->user()->hasCompletedOnboarding(),
                    'current_organization' => $request->user()->currentOrganization()?->only(['id', 'name', 'slug']),
                ] : null,
                'subscription' => $subscription,
            ],
        ];
    }
}
