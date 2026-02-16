<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useStatistics } from '@/Composables/useStatistics';
import { formatDate } from '@/utils/formatDate';
import { route } from '@/route';

const breadcrumbs = [{ label: 'Tableau de bord' }];

const props = defineProps({
    recentContracts: { type: Array, default: () => [] },
});

const {
    revenusTotaux,
    contratsActifs,
    clients,
    vehicules,
    loading,
    error,
} = useStatistics();

function formatXOF(value) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value) + ' F CFA';
}

const statusLabels = {
    draft: 'En attente',
    validated: 'Validé',
    active: 'Actif',
    cancelled: 'Annulé',
    expired: 'Expiré',
};
function statusLabel(status) {
    return status && statusLabels[status] ? statusLabels[status] : status ?? '—';
}
function statusBadgeClass(status) {
    const s = String(status ?? '').toLowerCase();
    if (['active', 'validated'].includes(s)) return 'bg-sky-100 text-sky-800';
    if (['cancelled', 'expired'].includes(s)) return 'bg-red-100 text-red-800';
    return 'bg-amber-100 text-amber-800';
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Tableau de bord" />
        </template>

        <div class="flex-1 min-h-full flex flex-col w-full">
            <!-- KPIs -->
            <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4 mb-6">
                {{ error }}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500 mb-0.5">Revenus totaux générés</p>
                    <p v-if="loading" class="text-xl font-semibold text-slate-400">—</p>
                    <p v-else class="text-xl font-semibold text-slate-900">{{ formatXOF(revenusTotaux) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500 mb-0.5">Total des contrats</p>
                    <p v-if="loading" class="text-xl font-semibold text-slate-400">—</p>
                    <p v-else class="text-xl font-semibold text-slate-900">{{ contratsActifs }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500 mb-0.5">Total des clients</p>
                    <p v-if="loading" class="text-xl font-semibold text-slate-400">—</p>
                    <p v-else class="text-xl font-semibold text-slate-900">{{ clients }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500 mb-0.5">Total des véhicules</p>
                    <p v-if="loading" class="text-xl font-semibold text-slate-400">—</p>
                    <p v-else class="text-xl font-semibold text-slate-900">{{ vehicules }}</p>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 md:p-8 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Actions rapides</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Accédez rapidement aux fonctionnalités principales</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="route('contracts.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nouveau contrat
                        </Link>
                        <Link
                            :href="route('clients.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-200 bg-white text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Nouveau client
                        </Link>
                        <Link
                            :href="route('vehicles.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-200 bg-white text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Nouveau véhicule
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Contrats récents -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden mb-6">
                <div class="p-4 md:p-6 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Contrats récents</h2>
                    <p class="text-sm text-slate-500 mt-0.5">15 derniers contrats</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">Date</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">Client</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">Véhicule</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">Montant</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">Statut</th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in props.recentContracts"
                                :key="row.id"
                                class="border-b border-slate-100 hover:bg-slate-50/50"
                            >
                                <td class="py-3 px-4 text-slate-700">{{ formatDate(row.created_at) }}</td>
                                <td class="py-3 px-4 text-slate-900">{{ row.client }}</td>
                                <td class="py-3 px-4 text-slate-700">{{ row.vehicle }}</td>
                                <td class="py-3 px-4 text-slate-900">
                                    {{ row.total_amount != null ? formatXOF(row.total_amount) : '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(row.status)]"
                                    >
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link
                                        :href="route('contracts.show', row.id)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                                        title="Voir le contrat"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="props.recentContracts.length === 0">
                                <td colspan="6" class="py-8 px-4 text-center text-slate-500">
                                    Aucun contrat. <Link :href="route('contracts.create')" class="text-sky-600 hover:underline">Créer un contrat</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
