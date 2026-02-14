<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;

class AssignDefaultPlansCommand extends Command
{
    protected $signature = 'subscription:assign-default';

    protected $description = 'Assigne le plan gratuit aux organisations sans abonnement actif';

    public function handle(): int
    {
        $defaultPlan = Plan::default();

        if (! $defaultPlan) {
            $this->error('Aucun plan par défaut. Exécutez : ddev php artisan db:seed --class=PlanSeeder');
            return Command::FAILURE;
        }

        $organizations = Organization::whereDoesntHave('subscriptions', fn ($q) =>
            $q->whereIn('status', ['trial', 'active'])
              ->where(fn ($q2) => $q2->whereNull('expires_at')->orWhere('expires_at', '>', now()))
        )->get();

        $count = 0;
        foreach ($organizations as $org) {
            Subscription::create([
                'organization_id' => $org->id,
                'plan_id' => $defaultPlan->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);
            $count++;
            $this->line("  {$org->name} → {$defaultPlan->name}");
        }

        $this->info("{$count} organisation(s) mise(s) à jour.");

        return Command::SUCCESS;
    }
}
