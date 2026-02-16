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
import { contractTypeLabel } from '@/utils/contractTypes';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    contracts: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Contrats' },
];

/** Référence contrat : CA- + 11 caractères alphanumériques majuscules. */
function contractReference(row) {
    return row.reference ?? '—';
}

const columns = [
    { key: 'created_at', label: 'Date de création', getValue: (row) => formatDate(row.created_at) },
    {
        key: 'reference',
        label: 'Référence',
        type: 'link',
        getValue: (row) => contractReference(row),
        href: (row) => route('contracts.show', row.id),
    },
    {
        key: 'deal_type',
        label: 'Affaire',
        type: 'badge',
        getValue: (row) => row.parent_id ? 'Renouvellement' : 'Nouvelle affaire',
        getBadgeClass: (row) => row.parent_id ? 'bg-violet-100 text-violet-800' : 'bg-emerald-100 text-emerald-800',
    },
    {
        key: 'vehicle',
        label: 'Véhicule',
        getValue: (row) => row.vehicle?.registration_number
            ?? [row.vehicle?.brand?.name, row.vehicle?.model?.name].filter(Boolean).join(' ')
            ?? '—',
    },
    { key: 'contract_type', label: 'Type', getValue: (row) => contractTypeLabel(row.contract_type) },
    { key: 'start_date', label: 'Date début', getValue: (row) => formatDate(row.start_date) },
    { key: 'end_date', label: 'Date fin', getValue: (row) => formatDate(row.end_date) },
    {
        key: 'prime',
        label: 'Prime',
        getValue: (row) => row.total_amount != null ? Number(row.total_amount).toLocaleString('fr-FR') + ' FCFA' : '—',
    },
    {
        key: 'status',
        label: 'Statut',
        type: 'badge',
        getValue: (row) => {
            const s = row.status ?? '—';
            const labels = { draft: 'Brouillon', validated: 'Validé', active: 'Actif', cancelled: 'Annulé', expired: 'Expiré' };
            return typeof s === 'string' && labels[s] ? labels[s] : s;
        },
        getBadgeClass: (row) => {
            const s = String(row.status ?? '').toLowerCase();
            if (['expired', 'expiré'].some((x) => s.includes(x)) || s === 'expire') return 'bg-red-100 text-red-800';
            if (['cancelled', 'annulé'].some((x) => s.includes(x))) return 'bg-red-100 text-red-800';
            if (['active', 'actif', 'validated', 'validé'].some((x) => s.includes(x))) return 'bg-emerald-100 text-emerald-800';
            return 'bg-slate-100 text-slate-800';
        },
    },
];

const queryParams = computed(() => ({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    per_page: props.contracts?.per_page ?? 25,
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
}));

const hasActiveFilters = computed(() => !!(
    props.filters?.search || props.filters?.status || props.filters?.date_from || props.filters?.date_to
));

function canEdit(row) {
    return row.status === 'draft';
}
function canCancel(row) {
    return ['draft', 'validated', 'active'].includes(row.status);
}
const { confirm: confirmDialog } = useConfirm();
function cancel(contract, label) {
    confirmDialog({
        title: 'Annuler le contrat',
        message: `Voulez-vous annuler le contrat « ${label} » ?`,
        confirmLabel: 'Annuler le contrat',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.post(route('contracts.cancel', contract.id));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Contrats">
                <template #actions>
                    <Link
                        :href="route('contracts.create')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Nouveau contrat
                    </Link>
                </template>
            </PageHeader>
        </template>

        <TableFilters
            action="/contracts"
            reset-href="/contracts"
            :has-active-filters="hasActiveFilters"
        >
            <input
                type="search"
                name="search"
                :value="filters?.search"
                placeholder="Rechercher (réf., type, client, compagnie, immat)..."
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-72 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
            />
            <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none">
                <option value="">Tous les statuts</option>
                <option value="draft" :selected="filters?.status === 'draft'">Brouillon</option>
                <option value="validated" :selected="filters?.status === 'validated'">Validé</option>
                <option value="active" :selected="filters?.status === 'active'">Actif</option>
                <option value="cancelled" :selected="filters?.status === 'cancelled'">Annulé</option>
                <option value="expired" :selected="filters?.status === 'expired'">Expiré</option>
            </select>
            <div class="flex items-center gap-2">
                <label class="text-sm text-slate-600 whitespace-nowrap">Période</label>
                <input
                    type="date"
                    name="date_from"
                    :value="filters?.date_from"
                    placeholder="Du"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-36 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    title="Date de début de période"
                />
                <span class="text-slate-400">→</span>
                <input
                    type="date"
                    name="date_to"
                    :value="filters?.date_to"
                    placeholder="Au"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-36 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    title="Date de fin de période"
                />
            </div>
            <input type="hidden" name="per_page" :value="contracts?.per_page ?? 25" />
        </TableFilters>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <DataTable
                :data="contracts.data ?? []"
                :columns="columns"
                row-key="id"
                empty-message="Aucun contrat."
            >
                <template #actions="{ row }">
                    <DataTableAction
                        label="Voir le détail"
                        :to="route('contracts.show', row.id)"
                        icon="eye"
                    />
                    <DataTableAction
                        label="Télécharger le contrat (PDF)"
                        :href="route('contracts.pdf', row.id)"
                        icon="download"
                        external
                    />
                    <DataTableAction
                        label="Renouveler"
                        :to="route('contracts.renew', row.id)"
                        icon="refresh"
                    />
                    <DataTableAction
                        v-if="canEdit(row)"
                        label="Modifier"
                        :to="route('contracts.edit', row.id)"
                        icon="edit"
                    />
                    <DataTableAction
                        v-if="canCancel(row)"
                        label="Annuler le contrat"
                        variant="warning"
                        icon="x"
                        @click="cancel(row, (contractReference(row) + ' – ' + (contractTypeLabel(row.contract_type) || '') + ' ' + (row.company?.name || '')))"
                    />
                </template>
                <template #empty>
                    Aucun contrat. <Link :href="route('contracts.create')" class="text-slate-900 underline">Créer un contrat</Link>
                </template>
            </DataTable>
            <Paginator
                v-if="contracts"
                :paginator="contracts"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
