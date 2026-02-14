<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ActivateSubscriptionCommand extends Command
{
    protected $signature = 'subscription:activate 
                            {organization : Slug ou ID de l\'organisation} 
                            {plan : Slug du plan (free, pro, entreprise)} 
                            {--months=1 : Durée en mois}';

    protected $description = 'Active manuellement un abonnement pour une organisation';

    public function handle(): int
    {
        $orgInput = $this->argument('organization');
        $planSlug = $this->argument('plan');
        $months = (int) $this->option('months');

        $organization = Organization::where('slug', $orgInput)
            ->orWhere('id', $orgInput)
            ->first();

        if (! $organization) {
            $this->error("Organisation non trouvée : {$orgInput}");
            return Command::FAILURE;
        }

        $plan = Plan::where('slug', $planSlug)->first();

        if (! $plan) {
            $this->error("Plan non trouvé : {$planSlug}");
            $this->line('Plans disponibles : free, pro, entreprise');
            return Command::FAILURE;
        }

        $admin = $organization->users()->wherePivot('role', 'admin')->first();
        $user = $admin ?? $organization->users()->first();

        $expiresAt = $months > 0 ? now()->addMonths($months) : null;

        $subscription = app(SubscriptionService::class)->activate(
            $organization,
            $plan,
            $user,
            $expiresAt
        );

        $this->info("Abonnement activé : {$organization->name} → {$plan->name}");
        $this->line("Expire : " . ($expiresAt?->format('d/m/Y') ?? 'jamais'));

        return Command::SUCCESS;
    }
}
