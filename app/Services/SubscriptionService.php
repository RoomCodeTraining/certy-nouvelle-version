<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    public function getActiveSubscription(Organization $organization): ?Subscription
    {
        return $organization->subscriptions()
            ->with('plan')
            ->whereIn('status', ['trial', 'active'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    public function canUploadDocument(Organization $organization): bool
    {
        return false; // Archivage désactivé (focus assurance auto)
    }

    public function getDocumentsRemaining(Organization $organization): ?int
    {
        return null; // Archivage désactivé
    }

    public function canUseAssistant(Organization $organization): bool
    {
        $sub = $this->getActiveSubscription($organization);
        if (! $sub) {
            return false;
        }
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $used = AiUsageLog::where('organization_id', $organization->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $used < $sub->plan->limits_assistant_calls_per_month;
    }

    public function getAssistantCallsRemaining(Organization $organization): ?int
    {
        $sub = $this->getActiveSubscription($organization);
        if (! $sub) {
            return null;
        }
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $used = AiUsageLog::where('organization_id', $organization->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return max(0, $sub->plan->limits_assistant_calls_per_month - $used);
    }

    public function logAiUsage(
        Organization $organization,
        string $provider,
        string $model,
        int $inputTokens = 0,
        int $outputTokens = 0,
        ?int $costCents = null
    ): void {
        AiUsageLog::create([
            'organization_id' => $organization->id,
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents' => $costCents,
        ]);
    }

    public function activate(Organization $organization, Plan $plan, User $admin, ?Carbon $expiresAt = null): Subscription
    {
        $organization->subscriptions()->whereIn('status', ['trial', 'active'])->update(['status' => 'cancelled']);

        return Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'activated_by' => $admin->id,
            'activated_at' => now(),
            'expires_at' => $expiresAt ?? now()->addMonth(),
        ]);
    }
}
