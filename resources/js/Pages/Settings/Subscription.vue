<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    currentSubscription: Object,
    plans: Array,
    organizationSlug: String,
    billingEmail: String,
});

const requestPlanUrl = (planSlug) => {
    const subject = encodeURIComponent(`Demande plan ${planSlug} - ${props.organizationSlug}`);
    const body = encodeURIComponent(
        `Bonjour,\n\nJe souhaite passer au plan ${planSlug} pour l'organisation ${props.organizationSlug}.\n\nMerci.`
    );
    return `mailto:${props.billingEmail}?subject=${subject}&body=${body}`;
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Abonnement</h1>
        </template>

        <div class="flex-1 min-h-full flex flex-col w-full">
            <p class="text-slate-600 text-sm mb-6">
                Votre plan actuel et les offres disponibles. L'activation des plans payants est manuelle — contactez-nous pour passer à un plan supérieur.
            </p>

            <!-- Plan actuel -->
            <div v-if="currentSubscription" class="rounded-xl border border-slate-200 bg-white p-6 mb-8">
                <h2 class="text-base font-semibold text-slate-900 mb-4">Plan actuel</h2>
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-lg font-medium text-slate-900">{{ currentSubscription.plan_name }}</span>
                    <span v-if="currentSubscription.expires_at" class="text-sm text-slate-500">
                        Valide jusqu'au {{ new Date(currentSubscription.expires_at).toLocaleDateString('fr-FR') }}
                    </span>
                </div>
                <div class="mt-4 flex gap-6 text-sm text-slate-600">
                    <span>{{ currentSubscription.documents_limit - currentSubscription.documents_remaining }} / {{ currentSubscription.documents_limit }} documents</span>
                    <span>{{ currentSubscription.assistant_calls_remaining }} / {{ currentSubscription.assistant_calls_limit }} appels assistant ce mois</span>
                </div>
            </div>

            <!-- Plans disponibles -->
            <h2 class="text-base font-semibold text-slate-900 mb-4">Plans disponibles</h2>
            <div class="space-y-4 flex-1">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-xl border p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    :class="currentSubscription?.plan_slug === plan.slug ? 'border-teal-300 bg-teal-50/50' : 'border-slate-200 bg-white'"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-semibold text-slate-900">{{ plan.name }}</span>
                            <span v-if="currentSubscription?.plan_slug === plan.slug" class="text-xs font-medium text-teal-700 bg-teal-100 px-2 py-0.5 rounded">Actuel</span>
                        </div>
                        <p class="text-lg font-medium text-slate-700 mt-1">{{ plan.price_formatted }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            <li>{{ plan.limits_documents }} documents</li>
                            <li>{{ plan.limits_assistant_calls_per_month }} appels assistant / mois</li>
                            <li v-for="(f, i) in plan.features" :key="i">{{ f }}</li>
                        </ul>
                    </div>
                    <div>
                        <a
                            v-if="currentSubscription?.plan_slug !== plan.slug && plan.slug !== 'free'"
                            :href="requestPlanUrl(plan.slug)"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors"
                        >
                            Demander ce plan
                        </a>
                        <span v-else-if="plan.slug === 'free'" class="text-sm text-slate-500">Plan par défaut</span>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-sm text-slate-500">
                Après réception de votre demande, nous activerons votre plan sous 48h. Pour une activation immédiate en environnement de test, l'administrateur peut exécuter :<br>
                <code class="mt-2 block rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ organizationSlug ? `ddev php artisan subscription:activate ${organizationSlug} pro` : 'ddev php artisan subscription:activate &lt;slug-org&gt; pro' }}</code>
            </p>

            <div class="mt-8">
                <Link
                    href="/dashboard"
                    class="text-sm text-slate-600 hover:text-slate-900"
                >
                    ← Retour au tableau de bord
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
