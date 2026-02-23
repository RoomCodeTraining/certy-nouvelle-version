<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
    clients: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Clients' },
];

const columns = [
    { key: 'created_at', label: 'Date', sortKey: 'created_at', getValue: (row) => formatDate(row.created_at) },
    { key: 'reference', label: 'Référence', sortKey: 'reference', getValue: (row) => row.reference ?? '—' },
    { key: 'full_name', label: 'Nom complet', sortKey: 'full_name', type: 'link', getValue: (row) => row.full_name ?? '—', href: (row) => route('clients.show', row.id) },
    { key: 'owner', label: 'Propriétaire', getValue: (row) => row.owner?.name ?? '—' },
    { key: 'email', label: 'Email', getValue: (row) => row.email ?? '—' },
    { key: 'phone', label: 'Téléphone', getValue: (row) => row.phone ?? '—' },
    { key: 'address', label: 'Adresse', getValue: (row) => row.address ?? '—' },
    { key: 'postal_address', label: 'Adresse postale', getValue: (row) => row.postal_address ?? '—' },
];

const queryParams = computed(() => ({
    search: props.filters?.search ?? '',
    type_assure: props.filters?.type_assure ?? '',
    per_page: props.clients?.per_page ?? 25,
    sort: props.filters?.sort ?? 'created_at',
    order: props.filters?.order ?? 'desc',
}));

const hasActiveFilters = computed(() => !!(props.filters?.search || props.filters?.type_assure));

const { confirm: confirmDialog } = useConfirm();
function destroy(client, clientName) {
    confirmDialog({
        title: 'Supprimer le client',
        message: `Voulez-vous vraiment supprimer le client « ${clientName} » ?`,
        confirmLabel: 'Supprimer',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.delete(route('clients.destroy', client.id));
    });
}

</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Clients">
                <template #actions>
                    <Link
                        :href="route('clients.create')"
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 bg-slate-900 text-white text-sm font-medium rounded-xl sm:rounded-lg hover:bg-slate-800"
                    >
                        Nouveau client
                    </Link>
                </template>
            </PageHeader>
        </template>

        <TableFilters
            action="/clients"
            reset-href="/clients"
            :has-active-filters="hasActiveFilters"
        >
            <input
                type="search"
                name="search"
                :value="filters?.search"
                placeholder="Rechercher (réf., nom, email, tél.)..."
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-64 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
            />
            <select name="type_assure" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none">
                <option value="">Tous types</option>
                <option value="TAPP" :selected="filters?.type_assure === 'TAPP'">TAPP</option>
                <option value="TAPM" :selected="filters?.type_assure === 'TAPM'">TAPM</option>
            </select>
            <input type="hidden" name="per_page" :value="clients?.per_page ?? 25" />
            <input type="hidden" name="sort" :value="filters?.sort ?? 'created_at'" />
            <input type="hidden" name="order" :value="filters?.order ?? 'desc'" />
        </TableFilters>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div
                    v-for="row in (clients?.data ?? [])"
                    :key="row.id"
                    class="p-4"
                >
                    <Link :href="route('clients.show', row.id)" class="block active:bg-slate-50/80 rounded-lg -m-2 p-2 transition-colors">
                        <p class="font-medium text-slate-900">{{ row.full_name ?? '—' }}</p>
                        <p class="text-sm text-slate-600 mt-0.5">{{ row.reference ?? '—' }}</p>
                        <p v-if="row.owner?.name" class="text-xs text-slate-500 mt-1">Propriétaire : {{ row.owner.name }}</p>
                        <p class="text-xs text-slate-500 mt-1 truncate">{{ row.email ?? '—' }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ row.phone ?? '—' }}</p>
                    </Link>
                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-slate-100">
                        <DataTableAction label="Voir" :to="route('clients.show', row.id)" icon="eye" />
                        <DataTableAction label="Modifier" :to="route('clients.edit', row.id)" icon="edit" />
                        <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, row.full_name)" />
                    </div>
                </div>
                <EmptyState
                    v-if="!(clients?.data?.length)"
                    title="Aucun client"
                    description="Ajoutez votre premier client pour commencer à gérer vos contrats."
                    cta-label="Créer un client"
                    :cta-href="route('clients.create')"
                    icon="folder"
                />
            </div>

            <!-- Desktop : tableau -->
            <div class="hidden md:block overflow-hidden">
                <DataTable
                    :data="clients.data ?? []"
                    :columns="columns"
                    row-key="id"
                    sort-base-url="/clients"
                    :sort-key="filters?.sort ?? 'created_at'"
                    :sort-order="filters?.order ?? 'desc'"
                    :sort-query-params="queryParams"
                >
                    <template #actions="{ row }">
                        <DataTableAction label="Voir le détail" :to="route('clients.show', row.id)" icon="eye" />
                        <DataTableAction label="Modifier" :to="route('clients.edit', row.id)" icon="edit" />
                        <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, row.full_name)" />
                    </template>
                    <template #empty>
                        <EmptyState
                            title="Aucun client"
                            description="Ajoutez votre premier client pour commencer à gérer vos contrats."
                            cta-label="Créer un client"
                            :cta-href="route('clients.create')"
                            icon="folder"
                        />
                    </template>
                </DataTable>
            </div>
            <Paginator
                v-if="clients"
                :paginator="clients"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
