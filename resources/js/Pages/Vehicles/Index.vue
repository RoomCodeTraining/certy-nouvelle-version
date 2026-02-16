<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import TableFilters from '@/Components/TableFilters.vue';
import Paginator from '@/Components/Paginator.vue';
import { route } from '@/route';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    vehicles: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Véhicules' },
];

const columns = [
    { key: 'created_at', label: 'Date de création', getValue: (row) => formatDate(row.created_at) },
    {
        key: 'registration_number',
        label: 'Immatriculation',
        type: 'link',
        getValue: (row) => row.registration_number ?? '—',
        href: (row) => route('vehicles.show', row.id),
    },
    { key: 'brand', label: 'Marque', getValue: (row) => row.brand?.name ?? '—' },
    { key: 'model', label: 'Modèle', getValue: (row) => row.model?.name ?? '—' },
    { key: 'seat_count', label: 'Places', getValue: (row) => row.seat_count != null ? row.seat_count : '—' },
    { key: 'color', label: 'Couleur', getValue: (row) => row.color?.name ?? '—' },
    { key: 'first_registration_date', label: 'Date 1ère immat.', getValue: (row) => formatDate(row.first_registration_date) },
    { key: 'registration_card_number', label: 'N° carte grise', getValue: (row) => row.registration_card_number ?? '—' },
    { key: 'chassis_number', label: 'N° châssis', getValue: (row) => row.chassis_number ?? '—' },
];

const queryParams = computed(() => ({
    search: props.filters?.search ?? '',
    per_page: props.vehicles?.per_page ?? 25,
}));

const hasActiveFilters = computed(() => !!props.filters?.search);

const { confirm: confirmDialog } = useConfirm();
function destroy(vehicle, label) {
    confirmDialog({
        title: 'Supprimer le véhicule',
        message: `Voulez-vous vraiment supprimer le véhicule « ${label} » ?`,
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
            <PageHeader :breadcrumbs="breadcrumbs" title="Véhicules">
                <template #actions>
                    <Link
                        :href="route('vehicles.create')"
                        class="inline-flex items-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Ajouter un véhicule
                    </Link>
                </template>
            </PageHeader>
        </template>

        <TableFilters
            action="/vehicles"
            reset-href="/vehicles"
            :has-active-filters="hasActiveFilters"
        >
            <input
                type="search"
                name="search"
                :value="filters?.search"
                placeholder="Rechercher (immat, client, marque, modèle)..."
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-72 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
            />
            <input type="hidden" name="per_page" :value="vehicles?.per_page ?? 25" />
        </TableFilters>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <DataTable
                :data="vehicles.data ?? []"
                :columns="columns"
                row-key="id"
                empty-message="Aucun véhicule. Créez d’abord un client puis ajoutez un véhicule depuis sa fiche."
            >
                <template #actions="{ row }">
                    <DataTableAction label="Voir le détail" :to="route('vehicles.show', row.id)" icon="eye" />
                    <DataTableAction label="Modifier" :to="route('vehicles.edit', row.id)" icon="edit" />
                    <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, (row.brand?.name + ' ' + row.model?.name) || row.registration_number)" />
                </template>
            </DataTable>
            <Paginator
                v-if="vehicles"
                :paginator="vehicles"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
