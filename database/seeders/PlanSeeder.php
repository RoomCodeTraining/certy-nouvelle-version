<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::where('is_default', true)->update(['is_default' => false]);

        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Gratuit',
                'price_monthly' => 0,
                'limits_documents' => 5,
                'limits_assistant_calls_per_month' => 20,
                'limits_ai_tokens_per_month' => null,
                'features' => ['Documents', 'Assistant basique'],
                'is_default' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price_monthly' => 65000,
                'limits_documents' => 100,
                'limits_assistant_calls_per_month' => 500,
                'limits_ai_tokens_per_month' => 1_000_000,
                'features' => ['100 documents', '500 appels assistant/mois', 'OpenAI prêt'],
                'is_default' => false,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'entreprise'],
            [
                'name' => 'Entreprise',
                'price_monthly' => 196000,
                'limits_documents' => 1000,
                'limits_assistant_calls_per_month' => 2000,
                'limits_ai_tokens_per_month' => 5_000_000,
                'features' => ['1000 documents', '2000 appels assistant/mois', 'Support prioritaire'],
                'is_default' => false,
            ]
        );
    }
}
