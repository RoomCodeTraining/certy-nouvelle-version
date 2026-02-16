<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PrintLayout from '@/Layouts/PrintLayout.vue';
import { route } from '@/route';
import { contractTypeLabel } from '@/utils/contractTypes';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    contract: Object,
});

const totalDisplay = computed(() =>
    Number(props.contract?.total_amount ?? props.contract?.total_after_reduction ?? props.contract?.total_premium ?? 0)
);

function printPage() {
    window.print();
}
</script>

<template>
    <PrintLayout>
        <div class="max-w-3xl mx-auto p-6 sm:p-8 text-slate-800">
            <!-- Barre d'action (masquée à l'impression) -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-8 print:hidden border-b border-slate-200 pb-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('contracts.show', contract.id)"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900"
                    >
                        ← Retour au contrat
                    </Link>
                    <span class="text-slate-400">|</span>
                    <p class="text-sm text-slate-600">
                        <kbd class="px-1.5 py-0.5 rounded bg-slate-100 text-xs">Ctrl+P</kbd> pour enregistrer en PDF
                    </p>
                </div>
                <button
                    type="button"
                    class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                    @click="printPage"
                >
                    Imprimer / Enregistrer en PDF
                </button>
            </div>

            <header class="mb-8">
                <h1 class="text-xl font-bold text-slate-900">Contrat — {{ contractTypeLabel(contract.contract_type) }} {{ contract.company?.name ?? '' }}</h1>
                <p class="text-sm text-slate-500 mt-1">Référence contrat · {{ formatDate(contract.start_date) }} — {{ formatDate(contract.end_date) }}</p>
            </header>

            <section class="mb-8">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Parties</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Client</dt>
                        <dd class="font-medium text-slate-900">{{ contract.client?.full_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Véhicule</dt>
                        <dd class="font-medium text-slate-900">
                            {{ contract.vehicle?.brand?.name }} {{ contract.vehicle?.model?.name }}
                            <span v-if="contract.vehicle?.registration_number">· {{ contract.vehicle.registration_number }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Compagnie</dt>
                        <dd class="font-medium text-slate-900">{{ contract.company?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Type</dt>
                        <dd class="font-medium text-slate-900">{{ contractTypeLabel(contract.contract_type) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="mb-8">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Période</h2>
                <p class="text-sm">
                    <span class="text-slate-600">Date d'effet :</span> {{ formatDate(contract.start_date) }}
                    <span class="mx-2 text-slate-300">·</span>
                    <span class="text-slate-600">Date d'échéance :</span> {{ formatDate(contract.end_date) }}
                </p>
            </section>

            <section v-if="contract.total_premium != null || contract.total_amount != null" class="mb-8">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Prime</h2>
                <dl class="space-y-2 text-sm">
                    <div v-if="contract.prime_ttc != null" class="flex justify-between">
                        <dt class="text-slate-600">Prime TTC</dt>
                        <dd class="font-medium tabular-nums">{{ Number(contract.prime_ttc).toLocaleString('fr-FR') }} FCFA</dd>
                    </div>
                    <div v-if="(contract.commission_amount || 0) > 0" class="flex justify-between">
                        <dt class="text-slate-600">Commission</dt>
                        <dd class="font-medium tabular-nums">+ {{ Number(contract.commission_amount).toLocaleString('fr-FR') }} FCFA</dd>
                    </div>
                    <div v-if="(contract.total_reduction_amount || 0) > 0" class="flex justify-between">
                        <dt class="text-slate-600">Réductions</dt>
                        <dd class="font-medium tabular-nums text-red-600">− {{ Number(contract.total_reduction_amount).toLocaleString('fr-FR') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between pt-2 border-t-2 border-slate-200 mt-2">
                        <dt class="font-semibold text-slate-700">Total à payer</dt>
                        <dd class="font-bold text-slate-900 tabular-nums text-lg">{{ totalDisplay.toLocaleString('fr-FR') }} FCFA</dd>
                    </div>
                </dl>
            </section>

            <footer class="mt-12 pt-6 border-t border-slate-200 text-xs text-slate-500 print:mt-8">
                Document généré à partir du contrat. Pour toute question, contactez votre assureur.
            </footer>
        </div>
    </PrintLayout>
</template>
