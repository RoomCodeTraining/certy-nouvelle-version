<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    rattachements: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Rattachements' },
];

// Backend peut envoyer soit le tableau, soit { data: [...], links, meta }
const list = Array.isArray(props.rattachements)
    ? props.rattachements
    : (Array.isArray(props.rattachements?.data) ? props.rattachements.data : []);

// Affichage sur deux lignes : nom puis code (whitespace-pre-line)
const columns = [
    {
        key: 'date_rattachement',
        label: 'Date de rattachement',
        getValue: (row) => row.formatted_created_at ?? formatDate(row.created_at),
    },
    {
        key: 'compagnie',
        label: 'Compagnie',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const o = row.owner;
            if (!o) return '—';
            const name = o.name ?? '—';
            const code = o.code ?? '';
            return code ? `${name}\n${code}` : name;
        },
    },
    {
        key: 'rattachee',
        label: 'Rattachée',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const r = row.related;
            if (!r) return '—';
            const name = r.name ?? '—';
            const code = r.code ?? '';
            return code ? `${name}\n${code}` : name;
        },
    },
    {
        key: 'import_production',
        label: 'Import Production',
        getValue: (row) => row.production_import_enabled === true ? 'Activé' : row.production_import_enabled === false ? 'Désactivé' : '—',
    },
    {
        key: 'created_at',
        label: 'Date de création',
        getValue: (row) => row.formatted_created_at ?? formatDate(row.created_at),
    },
    {
        key: 'statut',
        label: 'Statut',
        type: 'badge',
        getValue: (row) => row.is_disabled ? 'Désactivé' : 'Actif',
        getBadgeClass: (row) => row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800',
    },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Rattachements" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Données issues de la plateforme ASACI. Gestion des rattachements / relations.
        </p>

        <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ error }}
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-900">Liste des rattachements</h2>
                <Link href="/digital/rattachements" class="text-sm text-slate-600 hover:text-slate-900 font-medium">Actualiser</Link>
            </div>
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div v-for="row in list" :key="row.id" class="p-4">
                    <p class="text-xs text-slate-500">{{ row.formatted_created_at ?? formatDate(row.created_at) }}</p>
                    <p class="font-medium text-slate-900 mt-0.5">{{ row.owner?.name ?? '—' }} {{ row.owner?.code ? `(${row.owner.code})` : '' }}</p>
                    <p class="text-sm text-slate-700 mt-1">→ {{ row.related?.name ?? '—' }} {{ row.related?.code ? `(${row.related.code})` : '' }}</p>
                    <span
                        :class="['inline-flex mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium', row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']"
                    >
                        {{ row.is_disabled ? 'Désactivé' : 'Actif' }}
                    </span>
                </div>
                <div v-if="!list.length" class="py-10 px-4 text-center text-slate-500 text-sm">Aucun rattachement.</div>
            </div>
            <!-- Desktop : tableau -->
            <div class="hidden md:block">
                <DataTable
                    :data="list"
                    :columns="columns"
                    :row-key="(row) => row.id"
                    empty-message="Aucun rattachement."
                >
                    <template #actions>
                        <span class="text-slate-400">—</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </DashboardLayout>
</template>
