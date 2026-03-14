<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import { route } from '@/route';
import { contractTypeLabel } from '@/utils/contractTypes';
import { contractStatusLabel, contractStatusBadgeClass } from '@/utils/contractStatus';
import { formatDate } from '@/utils/formatDate';
import { expiresSoon } from '@/utils/expiresSoon';
import { useConfirm } from '@/Composables/useConfirm';

function formatXOF(value) {
    if (value == null) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' F CFA';
}

const props = defineProps({
    vehicle: Object,
});

const vehicleTitle = computed(() => {
    const v = props.vehicle;
    const name = [v?.brand?.name, v?.model?.name].filter(Boolean).join(' ') || 'Fiche véhicule';
    return v?.registration_number ? `${name} — ${v.registration_number}` : name;
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Véhicules', href: '/vehicles' },
    { label: vehicleTitle.value },
];

const { confirm: confirmDialog } = useConfirm();
function destroy(vehicle) {
    confirmDialog({
        title: 'Supprimer le véhicule',
        message: 'Voulez-vous vraiment supprimer ce véhicule ?',
        confirmLabel: 'Supprimer',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.delete(route('vehicles.destroy', vehicle.id));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="vehicleTitle">
                <template #actions>
                    <Link
                        :href="route('vehicles.edit', vehicle.id)"
                        class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                    >
                        Modifier
                    </Link>
                    <button
                        type="button"
                        class="px-4 py-2 border border-red-200 text-red-700 text-sm font-medium rounded-lg hover:bg-red-50"
                        @click="destroy(vehicle)"
                    >
                        Supprimer
                    </button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-sm font-medium text-slate-500 mb-3">Informations</h3>
                <dl class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                    <div><dt class="text-slate-500">Référence</dt><dd class="font-mono font-semibold text-slate-900">{{ vehicle.reference ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Client</dt><dd><Link :href="route('clients.show', vehicle.client?.id)" class="font-medium text-slate-900 hover:underline">{{ vehicle.client?.full_name }}</Link></dd></div>
                    <div><dt class="text-slate-500">Marque / Modèle</dt><dd class="font-medium text-slate-900">{{ [vehicle.brand?.name, vehicle.model?.name].filter(Boolean).join(' ') || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Type</dt><dd class="font-medium text-slate-900">{{ vehicle.pricing_type ? { VP: 'VP (Véhicule Particulier)', TPC: 'Transport pour propre compte', TPM: 'TPM', TWO_WHEELER: 'Deux roues' }[vehicle.pricing_type] || vehicle.pricing_type : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Immatriculation</dt><dd class="font-medium text-slate-900">{{ vehicle.registration_number || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Carrosserie</dt><dd class="font-medium text-slate-900">{{ vehicle.body_type || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Zone de circulation</dt><dd class="font-medium text-slate-900">{{ vehicle.circulation_zone?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Source d’énergie</dt><dd class="font-medium text-slate-900">{{ vehicle.energy_source?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Usage</dt><dd class="font-medium text-slate-900">{{ vehicle.vehicle_usage?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Type</dt><dd class="font-medium text-slate-900">{{ vehicle.vehicle_type?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Catégorie</dt><dd class="font-medium text-slate-900">{{ vehicle.vehicle_category?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Genre</dt><dd class="font-medium text-slate-900">{{ vehicle.vehicle_gender?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Couleur</dt><dd class="font-medium text-slate-900">{{ vehicle.color?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Charge utile (t)</dt><dd class="font-medium text-slate-900">{{ vehicle.payload_capacity != null ? vehicle.payload_capacity : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Cylindrée</dt><dd class="font-medium text-slate-900">{{ vehicle.engine_capacity ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Nombre de places</dt><dd class="font-medium text-slate-900">{{ vehicle.seat_count ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Puissance fiscale</dt><dd class="font-medium text-slate-900">{{ vehicle.fiscal_power ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Date 1re mise en circulation</dt><dd class="font-medium text-slate-900">{{ vehicle.first_registration_date ? formatDate(vehicle.first_registration_date) : (vehicle.year_of_first_registration ?? '—') }}</dd></div>
                    <div><dt class="text-slate-500">N° carte grise</dt><dd class="font-medium text-slate-900">{{ vehicle.registration_card_number || '—' }}</dd></div>
                    <div><dt class="text-slate-500">N° châssis</dt><dd class="font-medium text-slate-900">{{ vehicle.chassis_number || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Valeur neuve</dt><dd class="font-medium text-slate-900">{{ vehicle.new_value != null ? vehicle.new_value : '—' }}</dd></div>
                    <div><dt class="text-slate-500">Valeur de remplacement</dt><dd class="font-medium text-slate-900">{{ vehicle.replacement_value != null ? vehicle.replacement_value : '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Contrats</h3>
                    <Link
                        :href="`${route('contracts.create')}?vehicle_id=${vehicle.id}`"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-sky-700 bg-sky-50 rounded-lg hover:bg-sky-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouveau contrat
                    </Link>
                </div>
                <div v-if="vehicle.contracts?.length" class="p-4 sm:p-6">
                    <div class="grid gap-4 sm:gap-5">
                        <div
                            v-for="c in vehicle.contracts"
                            :key="c.id"
                            :class="[
                                'rounded-xl border p-4 sm:p-5 transition-colors',
                                expiresSoon(c.end_date, c.status)
                                    ? 'border-amber-200 bg-amber-50/50 hover:border-amber-300'
                                    : 'border-slate-200 bg-slate-50/30 hover:border-slate-300 hover:bg-slate-50/50',
                            ]"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <Link
                                            :href="route('contracts.show', c.id)"
                                            class="font-mono text-sm font-semibold text-slate-900 hover:text-sky-700 hover:underline"
                                        >
                                            {{ c.reference ?? '—' }}
                                        </Link>
                                        <span
                                            :class="['inline-flex shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium', contractStatusBadgeClass(c.status)]"
                                        >
                                            {{ contractStatusLabel(c.status) }}
                                        </span>
                                        <span
                                            v-if="expiresSoon(c.end_date, c.status)"
                                            class="inline-flex shrink-0 px-2 py-0.5 rounded text-xs font-medium bg-amber-200 text-amber-900"
                                        >
                                            Échéance proche
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-700">
                                        {{ contractTypeLabel(c.contract_type) }}
                                        <span v-if="c.company?.name" class="text-slate-600"> · {{ c.company.name }}</span>
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600">
                                        <span>
                                            {{ formatDate(c.start_date) }} → {{ formatDate(c.end_date) }}
                                        </span>
                                        <span v-if="c.total_amount != null" class="font-semibold text-slate-900">
                                            {{ formatXOF(c.total_amount) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <DataTableAction label="Voir" :to="route('contracts.show', c.id)" icon="eye" />
                                    <DataTableAction
                                        label="PDF"
                                        :href="route('contracts.pdf', c.id)"
                                        icon="download"
                                        external
                                    />
                                    <DataTableAction
                                        v-if="c.status !== 'draft'"
                                        label="Renouveler"
                                        :to="route('contracts.renew', c.id)"
                                        icon="refresh"
                                    />
                                    <DataTableAction label="Modifier" :to="route('contracts.edit', c.id)" icon="edit" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">Aucun contrat pour ce véhicule.</p>
                    <Link
                        :href="`${route('contracts.create')}?vehicle_id=${vehicle.id}`"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors"
                    >
                        Créer un contrat
                    </Link>
                </div>
            </div>

            <Link :href="route('vehicles.index')" class="inline-block text-sm text-slate-600 hover:text-slate-900">← Retour à la liste</Link>
        </div>
    </DashboardLayout>
</template>
