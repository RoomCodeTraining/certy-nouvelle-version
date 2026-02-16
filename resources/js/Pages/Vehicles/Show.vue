<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';
import { contractTypeLabel } from '@/utils/contractTypes';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

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
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-sm font-medium text-slate-900">Contrats</h3>
                </div>
                <ul class="divide-y divide-slate-200">
                    <li v-for="c in vehicle.contracts" :key="c.id" class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <Link :href="route('contracts.show', c.id)" class="font-medium text-slate-900 hover:underline">
                                {{ contractTypeLabel(c.contract_type) }} — {{ c.company?.name }}
                            </Link>
                            <span class="ml-2 text-xs text-slate-500">{{ c.status }}</span>
                        </div>
                        <Link :href="route('contracts.edit', c.id)" class="text-sm text-slate-600 hover:text-slate-900">Modifier</Link>
                    </li>
                </ul>
                <div v-if="!vehicle.contracts?.length" class="px-6 py-8 text-center text-slate-500 text-sm">
                    Aucun contrat pour ce véhicule.
                </div>
            </div>

            <Link :href="route('vehicles.index')" class="inline-block text-sm text-slate-600 hover:text-slate-900">← Retour à la liste</Link>
        </div>
    </DashboardLayout>
</template>
