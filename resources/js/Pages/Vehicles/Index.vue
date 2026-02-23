<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import TableFilters from '@/Components/TableFilters.vue';
import Paginator from '@/Components/Paginator.vue';
import EmptyState from '@/Components/EmptyState.vue';
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
    { key: 'reference', label: 'Référence', getValue: (row) => row.reference ?? '—' },
    {
        key: 'registration_number',
        label: 'Immatriculation',
        sortKey: 'registration_number',
        type: 'link',
        getValue: (row) => row.registration_number ?? '—',
        href: (row) => route('vehicles.show', row.id),
    },
    { key: 'owner', label: 'Propriétaire', getValue: (row) => row.client?.owner?.name ?? '—' },
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
    sort: props.filters?.sort ?? 'created_at',
    order: props.filters?.order ?? 'desc',
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
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 bg-slate-900 text-white text-sm font-medium rounded-xl sm:rounded-lg hover:bg-slate-800"
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
                placeholder="Rechercher (réf., immat, client, marque, modèle)..."
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-72 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
            />
            <input type="hidden" name="per_page" :value="vehicles?.per_page ?? 25" />
            <input type="hidden" name="sort" :value="filters?.sort ?? 'created_at'" />
            <input type="hidden" name="order" :value="filters?.order ?? 'desc'" />
        </TableFilters>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div
                    v-for="row in (vehicles?.data ?? [])"
                    :key="row.id"
                    class="p-4"
                >
                    <Link :href="route('vehicles.show', row.id)" class="block active:bg-slate-50/80 rounded-lg -m-2 p-2 transition-colors">
                        <p class="font-medium text-slate-900">{{ row.registration_number ?? '—' }}</p>
                        <p class="text-sm text-slate-700 mt-0.5">{{ row.brand?.name ?? '—' }} {{ row.model?.name ?? '' }}</p>
                        <p v-if="row.client?.owner?.name" class="text-xs text-slate-500 mt-1">Propriétaire : {{ row.client.owner.name }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ row.reference ?? '—' }}</p>
                    </Link>
                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-slate-100">
                        <DataTableAction label="Voir" :to="route('vehicles.show', row.id)" icon="eye" />
                        <DataTableAction label="Modifier" :to="route('vehicles.edit', row.id)" icon="edit" />
                        <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, (row.brand?.name + ' ' + row.model?.name) || row.registration_number)" />
                    </div>
                </div>
                <EmptyState
                    v-if="!(vehicles?.data?.length)"
                    title="Aucun véhicule"
                    description="Créez d'abord un client, puis ajoutez un véhicule depuis sa fiche ou ici."
                    cta-label="Ajouter un véhicule"
                    :cta-href="route('vehicles.create')"
                    icon="sparkles"
                />
            </div>

            <!-- Desktop : tableau -->
            <div class="hidden md:block overflow-hidden">
                <DataTable
                :data="vehicles.data ?? []"
                :columns="columns"
                row-key="id"
                sort-base-url="/vehicles"
                :sort-key="filters?.sort ?? 'created_at'"
                :sort-order="filters?.order ?? 'desc'"
                :sort-query-params="queryParams"
                empty-message="Aucun véhicule. Créez d’abord un client puis ajoutez un véhicule depuis sa fiche."
            >
                <template #empty>
                    <EmptyState
                        title="Aucun véhicule"
                        description="Créez d'abord un client, puis ajoutez un véhicule depuis sa fiche ou ici."
                        cta-label="Ajouter un véhicule"
                        :cta-href="route('vehicles.create')"
                        icon="sparkles"
                    />
                </template>
                <template #actions="{ row }">
                    <DataTableAction label="Voir le détail" :to="route('vehicles.show', row.id)" icon="eye" />
                    <DataTableAction label="Modifier" :to="route('vehicles.edit', row.id)" icon="edit" />
                    <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, (row.brand?.name + ' ' + row.model?.name) || row.registration_number)" />
                </template>
                </DataTable>
            </div>
            <Paginator
                v-if="vehicles"
                :paginator="vehicles"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
