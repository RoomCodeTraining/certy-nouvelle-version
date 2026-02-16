<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useStatistics } from '@/Composables/useStatistics';

const breadcrumbs = [{ label: 'Tableau de bord' }];

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

            <div class="rounded-xl border border-slate-200 bg-white p-8 mb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-1">Bienvenue sur Certy</h2>
                <p class="text-slate-600">Plateforme de gestion de l'assurance auto pour courtiers.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Link href="/clients" class="rounded-xl border border-slate-200 bg-white p-6 text-center hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Clients</p>
                    <p class="text-lg font-semibold text-slate-900">Gérer les clients</p>
                </Link>
                <Link href="/vehicles" class="rounded-xl border border-slate-200 bg-white p-6 text-center hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Véhicules</p>
                    <p class="text-lg font-semibold text-slate-900">Gérer les véhicules</p>
                </Link>
                <Link href="/contracts" class="rounded-xl border border-slate-200 bg-white p-6 text-center hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Contrats</p>
                    <p class="text-lg font-semibold text-slate-900">Gérer les contrats</p>
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
