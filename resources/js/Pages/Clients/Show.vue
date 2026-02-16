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
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' FCFA';
}

const typeAssureLabel = computed(() => {
    const t = props.client?.type_assure;
    if (t === 'TAPP') return 'Personne physique (TAPP)';
    if (t === 'TAPM') return 'Personne morale (TAPM)';
    return '—';
});

const statusLabels = { draft: 'Brouillon', validated: 'Validé', active: 'Actif', cancelled: 'Annulé', expired: 'Expiré' };
function statusLabel(s) {
    return (s && statusLabels[s]) ? statusLabels[s] : s ?? '—';
}
function statusClass(s) {
    const t = String(s ?? '').toLowerCase();
    if (['active', 'validated'].includes(t)) return 'bg-emerald-100 text-emerald-800';
    if (['cancelled', 'expired'].includes(t)) return 'bg-red-100 text-red-800';
    return 'bg-slate-100 text-slate-700';
}

const vehicles = computed(() => props.client?.vehicles ?? []);
const contracts = computed(() => props.client?.contracts ?? []);

function contractsForVehicle(vehicleId) {
    return contracts.value.filter((c) => c.vehicle_id === vehicleId);
}

function vehicleLabel(v) {
    if (!v) return '—';
    const brand = v.brand?.name ?? '';
    const model = v.model?.name ?? '';
    const reg = v.registration_number ?? '';
    return [brand, model].filter(Boolean).join(' ') + (reg ? ` — ${reg}` : '');
}

function dealTypeLabel(c) {
    return c.parent_id ? 'Renouvellement' : 'Nouvelle affaire';
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
                        Modifier
                    </Link>
                    <Link
                        :href="route('vehicles.create.for_client', client.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Ajouter un véhicule
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="flex-1 min-h-0 w-full p-4 md:p-6 pb-12 flex flex-col gap-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-2xl font-semibold text-slate-900 tabular-nums">{{ vehicles.length }}</p>
                    <p class="text-sm text-slate-500 mt-0.5">Véhicules</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-2xl font-semibold text-slate-900 tabular-nums">{{ contracts.length }}</p>
                    <p class="text-sm text-slate-500 mt-0.5">Contrats</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6 flex-1 min-h-0">
                <!-- Infos client -->
                <section class="lg:col-span-1 rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col min-h-0">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-900">Coordonnées</h2>
                    </div>
                    <div class="p-5 flex-1 overflow-auto">
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-slate-500">Référence</dt>
                                <dd class="font-mono font-medium text-slate-900 mt-0.5">{{ client?.reference ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Email</dt>
                                <dd class="text-slate-900 mt-0.5">{{ client?.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Téléphone</dt>
                                <dd class="text-slate-900 mt-0.5">{{ client?.phone || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Adresse</dt>
                                <dd class="text-slate-900 mt-0.5">{{ client?.address || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Adresse postale</dt>
                                <dd class="text-slate-900 mt-0.5">{{ client?.postal_address || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Profession</dt>
                                <dd class="text-slate-900 mt-0.5">{{ client?.profession?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Type assuré</dt>
                                <dd class="text-slate-900 mt-0.5">{{ typeAssureLabel }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <!-- Véhicules + Contrats -->
                <div class="lg:col-span-2 flex flex-col gap-6 min-h-0 overflow-auto">
                    <!-- Véhicules -->
                    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden shrink-0">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900">Véhicules</h2>
                            <Link
                                :href="route('vehicles.create.for_client', client.id)"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900"
                            >
                                Ajouter
                            </Link>
                        </div>
                        <div class="overflow-auto max-h-64">
                            <template v-if="!vehicles.length">
                                <div class="p-8 text-center text-slate-500 text-sm">
                                    Aucun véhicule.
                                    <Link :href="route('vehicles.create.for_client', client.id)" class="text-slate-900 font-medium hover:underline ml-1">Ajouter un véhicule</Link>
                                </div>
                            </template>
                            <table v-else class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Véhicule</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Contrats</th>
                                        <th class="text-right py-3 px-4 font-medium text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="v in vehicles"
                                        :key="v.id"
                                        class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50"
                                    >
                                        <td class="py-3 px-4">
                                            <Link :href="route('vehicles.show', v.id)" class="font-medium text-slate-900 hover:underline">
                                                {{ vehicleLabel(v) }}
                                            </Link>
                                            <p v-if="v.registration_number" class="text-slate-500 text-xs mt-0.5">{{ v.registration_number }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-slate-600">{{ contractsForVehicle(v.id).length }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <Link :href="route('vehicles.show', v.id)" class="text-slate-600 hover:text-slate-900 mr-3">Voir</Link>
                                            <Link :href="route('vehicles.edit', v.id)" class="text-slate-600 hover:text-slate-900">Modifier</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Contrats -->
                    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden flex-1 min-h-0 flex flex-col">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h2 class="text-sm font-semibold text-slate-900">Contrats</h2>
                        </div>
                        <div class="flex-1 overflow-auto min-h-0">
                            <template v-if="!contracts.length">
                                <div class="p-8 text-center text-slate-500 text-sm">
                                    Aucun contrat. Les contrats apparaissent ici une fois créés pour un véhicule de ce client.
                                </div>
                            </template>
                            <table v-else class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Réf.</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Véhicule</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Type</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Compagnie</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Période</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Montant</th>
                                        <th class="text-left py-3 px-4 font-medium text-slate-600">Statut</th>
                                        <th class="text-right py-3 px-4 font-medium text-slate-600"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="c in contracts"
                                        :key="c.id"
                                        class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50"
                                    >
                                        <td class="py-3 px-4 font-mono text-slate-900">{{ c.reference ?? '—' }}</td>
                                        <td class="py-3 px-4 text-slate-700">
                                            {{ c.vehicle?.registration_number || [c.vehicle?.brand?.name, c.vehicle?.model?.name].filter(Boolean).join(' ') || '—' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-700">{{ contractTypeLabel(c.contract_type) }}</td>
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
                                            <Link :href="route('contracts.show', c.id)" class="text-slate-600 hover:text-slate-900 font-medium">
                                                Voir →
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <div class="pt-2">
                <Link
                    :href="route('clients.index')"
                    class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900"
                >
                    ← Retour à la liste des clients
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
