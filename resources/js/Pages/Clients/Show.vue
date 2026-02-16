<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';
import { contractTypeLabel } from '@/utils/contractTypes';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    client: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Clients', href: '/clients' },
    { label: props.client?.full_name ?? 'Fiche client' },
];

function formatXOF(value) {
    if (value == null) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' F CFA';
}

const statusLabels = { draft: 'Brouillon', validated: 'Validé', active: 'Actif', cancelled: 'Annulé', expired: 'Expiré' };
function statusLabel(s) {
    return (s && statusLabels[s]) ? statusLabels[s] : s ?? '—';
}
function statusClass(s) {
    const t = String(s ?? '').toLowerCase();
    if (['active', 'validated'].includes(t)) return 'bg-sky-100 text-sky-800';
    if (['cancelled', 'expired'].includes(t)) return 'bg-red-100 text-red-800';
    return 'bg-amber-100 text-amber-800';
}

// Contrats groupés par véhicule
function contractsForVehicle(vehicleId) {
    const list = props.client?.contracts ?? [];
    return list.filter((c) => c.vehicle_id === vehicleId);
}

function vehicleLabel(v) {
    if (!v) return '—';
    const brand = v.brand?.name ?? '';
    const model = v.model?.name ?? '';
    const reg = v.registration_number ?? '';
    return [brand, model].filter(Boolean).join(' ') + (reg ? ` — ${reg}` : '');
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="client?.full_name ?? 'Fiche client'">
                <template #actions>
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                    </Link>
                    <Link
                        :href="route('vehicles.create.for_client', client.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Ajouter un véhicule
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="max-w-5xl mx-auto space-y-8 pb-12">
            <!-- Coordonnées -->
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <h2 class="text-base font-semibold text-slate-900">Coordonnées</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="flex gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Email</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.email || '—' }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Téléphone</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.phone || '—' }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3 sm:col-span-2 lg:col-span-1">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Adresse</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.address || '—' }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3 sm:col-span-2">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Adresse postale</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.postal_address || '—' }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Profession</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.profession?.name || '—' }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Type assuré</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ client?.type_assure || '—' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </section>

            <!-- Véhicules et leurs contrats -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">
                        Véhicules
                        <span v-if="client?.vehicles?.length" class="text-slate-500 font-normal ml-1">({{ client.vehicles.length }})</span>
                    </h2>
                    <Link
                        :href="route('vehicles.create.for_client', client.id)"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 inline-flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Ajouter un véhicule
                    </Link>
                </div>

                <div v-if="!client?.vehicles?.length" class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-12 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-200 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <p class="text-slate-600 font-medium">Aucun véhicule</p>
                    <p class="text-sm text-slate-500 mt-1 mb-4">Ajoutez un véhicule pour gérer les contrats d'assurance.</p>
                    <Link
                        :href="route('vehicles.create.for_client', client.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Ajouter un véhicule
                    </Link>
                </div>

                <div v-else class="space-y-6">
                    <article
                        v-for="v in client.vehicles"
                        :key="v.id"
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
                    >
                        <!-- En-tête véhicule -->
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-slate-200 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <Link
                                        :href="route('vehicles.show', v.id)"
                                        class="font-semibold text-slate-900 hover:text-slate-700 hover:underline"
                                    >
                                        {{ vehicleLabel(v) }}
                                    </Link>
                                    <p v-if="v.registration_number" class="text-xs text-slate-500 mt-0.5">{{ v.registration_number }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('vehicles.show', v.id)"
                                    class="text-sm font-medium text-slate-600 hover:text-slate-900"
                                >
                                    Voir la fiche
                                </Link>
                                <span class="text-slate-300">|</span>
                                <Link
                                    :href="route('vehicles.edit', v.id)"
                                    class="text-sm font-medium text-slate-600 hover:text-slate-900"
                                >
                                    Modifier
                                </Link>
                            </div>
                        </div>

                        <!-- Contrats de ce véhicule -->
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Contrats de ce véhicule</h4>
                            <div v-if="!contractsForVehicle(v.id).length" class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-center text-sm text-slate-500">
                                Aucun contrat pour ce véhicule.
                                <Link :href="route('contracts.create')" class="text-slate-900 font-medium hover:underline ml-1">Créer un contrat</Link>
                            </div>
                            <div v-else class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="text-left py-3 px-4 font-medium text-slate-600">Type</th>
                                            <th class="text-left py-3 px-4 font-medium text-slate-600">Compagnie</th>
                                            <th class="text-left py-3 px-4 font-medium text-slate-600">Période</th>
                                            <th class="text-left py-3 px-4 font-medium text-slate-600">Montant</th>
                                            <th class="text-left py-3 px-4 font-medium text-slate-600">Statut</th>
                                            <th class="text-right py-3 px-4 font-medium text-slate-600">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="c in contractsForVehicle(v.id)"
                                            :key="c.id"
                                            class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50"
                                        >
                                            <td class="py-3 px-4 text-slate-900">{{ contractTypeLabel(c.contract_type) }}</td>
                                            <td class="py-3 px-4 text-slate-700">{{ c.company?.name ?? '—' }}</td>
                                            <td class="py-3 px-4 text-slate-600">
                                                {{ formatDate(c.start_date) }} → {{ formatDate(c.end_date) }}
                                            </td>
                                            <td class="py-3 px-4 font-medium text-slate-900">{{ formatXOF(c.total_amount) }}</td>
                                            <td class="py-3 px-4">
                                                <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', statusClass(c.status)]">
                                                    {{ statusLabel(c.status) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <Link
                                                    :href="route('contracts.show', c.id)"
                                                    class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900 font-medium"
                                                >
                                                    Voir
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Lien retour -->
            <div class="pt-4">
                <Link
                    :href="route('clients.index')"
                    class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour à la liste des clients
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
