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
    draft_count: { type: Number, default: 0 },
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Contrats' },
];

/** Référence contrat : CA- + 11 caractères alphanumériques majuscules. */
function contractReference(row) {
    return row.reference ?? '—';
}

/** Badge Affaire : Nouvelle affaire (pas de parent) ou Renouvellement (parent_id présent). */
function dealTypeLabel(row) {
    return row.parent_id ? 'Renouvellement' : 'Nouvelle affaire';
}
function dealTypeBadgeClass(row) {
    return row.parent_id ? 'bg-violet-100 text-violet-800' : 'bg-emerald-100 text-emerald-800';
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
        getValue: (row) => dealTypeLabel(row),
        getBadgeClass: (row) => dealTypeBadgeClass(row),
        cellClass: 'whitespace-nowrap',
    },
    {
        key: 'vehicle',
        label: 'Véhicule',
        getValue: (row) => row.vehicle?.registration_number
            ?? [row.vehicle?.brand?.name, row.vehicle?.model?.name].filter(Boolean).join(' ')
            ?? '—',
    },
    { key: 'owner', label: 'Propriétaire', getValue: (row) => row.client?.owner?.name ?? '—' },
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

/** URL d'export Excel avec les filtres courants (sans per_page). */
const exportExcelUrl = computed(() => {
    const base = route('contracts.export');
    const params = new URLSearchParams();
    if (props.filters?.search) params.set('search', props.filters.search);
    if (props.filters?.status) params.set('status', props.filters.status);
    if (props.filters?.date_from) params.set('date_from', props.filters.date_from);
    if (props.filters?.date_to) params.set('date_to', props.filters.date_to);
    const qs = params.toString();
    return qs ? `${base}?${qs}` : base;
});

const hasActiveFilters = computed(() => !!(
    props.filters?.search || props.filters?.status || props.filters?.date_from || props.filters?.date_to
));

const statusLabelsMap = { draft: 'Brouillon', validated: 'Validé', active: 'Actif', cancelled: 'Annulé', expired: 'Expiré' };
function statusLabelFor(status) {
    return statusLabelsMap[status] ?? status ?? '—';
}
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
                    <a
                        :href="exportExcelUrl"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 rounded-xl sm:rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50"
                        target="_blank"
                        rel="noopener noreferrer"
                        download
                    >
                        Exporter Excel
                    </a>
                    <Link
                        :href="route('contracts.create')"
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 bg-slate-900 text-white text-sm font-medium rounded-xl sm:rounded-lg hover:bg-slate-800"
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
            <Link
                v-if="draft_count > 0"
                :href="`${route('contracts.index')}?status=draft`"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm font-medium hover:bg-amber-100"
            >
                Brouillons ({{ draft_count }})
            </Link>
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
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div
                    v-for="row in (contracts?.data ?? [])"
                    :key="row.id"
                    class="p-4"
                >
                    <Link :href="route('contracts.show', row.id)" class="block active:bg-slate-50/80 rounded-lg -m-2 p-2 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-mono text-sm font-medium text-slate-900">{{ contractReference(row) }}</p>
                            <span
                                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
                                :class="dealTypeBadgeClass(row)"
                            >
                                {{ dealTypeLabel(row) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-700 mt-0.5">{{ contractTypeLabel(row.contract_type) }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ row.vehicle?.registration_number || [row.vehicle?.brand?.name, row.vehicle?.model?.name].filter(Boolean).join(' ') || '—' }}</p>
                        <p v-if="row.client?.owner?.name" class="text-xs text-slate-500 mt-0.5">Propriétaire : {{ row.client.owner.name }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ formatDate(row.start_date) }} → {{ formatDate(row.end_date) }}</p>
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <span
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="row.status === 'active' || row.status === 'validated' ? 'bg-emerald-100 text-emerald-800' : ['cancelled', 'expired'].includes(row.status) ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800'"
                            >
                                {{ statusLabelFor(row.status) }}
                            </span>
                            <span class="text-sm font-medium text-slate-900">{{ row.total_amount != null ? Number(row.total_amount).toLocaleString('fr-FR') + ' FCFA' : '—' }}</span>
                        </div>
                    </Link>
                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-slate-100">
                        <DataTableAction label="Voir" :to="route('contracts.show', row.id)" icon="eye" />
                        <DataTableAction label="PDF" :href="route('contracts.pdf', row.id)" icon="download" external />
                        <DataTableAction v-if="row.status !== 'draft'" label="Renouveler" :to="route('contracts.renew', row.id)" icon="refresh" />
                        <DataTableAction v-if="canEdit(row)" label="Modifier" :to="route('contracts.edit', row.id)" icon="edit" />
                        <DataTableAction
                            v-if="canCancel(row)"
                            label="Annuler"
                            variant="warning"
                            icon="x"
                            @click="cancel(row, (contractReference(row) + ' – ' + (contractTypeLabel(row.contract_type) || '') + ' ' + (row.company?.name || '')))"
                        />
                    </div>
                </div>
                <div v-if="!(contracts?.data?.length)" class="py-10 px-4 text-center text-slate-500 text-sm">
                    Aucun contrat. <Link :href="route('contracts.create')" class="text-sky-600 hover:underline block mt-2">Créer un contrat</Link>
                </div>
            </div>

            <!-- Desktop : tableau -->
            <div class="hidden md:block overflow-hidden">
                <DataTable
                    :data="contracts.data ?? []"
                    :columns="columns"
                    row-key="id"
                    empty-message="Aucun contrat."
                >
                    <template #actions="{ row }">
                        <DataTableAction label="Voir le détail" :to="route('contracts.show', row.id)" icon="eye" />
                        <DataTableAction label="Télécharger le contrat (PDF)" :href="route('contracts.pdf', row.id)" icon="download" external />
                        <DataTableAction v-if="row.status !== 'draft'" label="Renouveler" :to="route('contracts.renew', row.id)" icon="refresh" />
                        <DataTableAction v-if="canEdit(row)" label="Modifier" :to="route('contracts.edit', row.id)" icon="edit" />
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
            </div>
            <Paginator
                v-if="contracts"
                :paginator="contracts"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
