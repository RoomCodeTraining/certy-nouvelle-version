<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    attestations: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
    filters: Object,
});

const list = Array.isArray(props.attestations)
    ? props.attestations
    : (Array.isArray(props.attestations?.data) ? props.attestations.data : []);
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Attestations' },
];

// API: reference, printed_at, certificate_type{}, certificate_variant{}, state{}, insured_name, licence_plate, starts_at, ends_at, download_link, organization{}, office{}
function formatPeriod(row) {
    const start = row.starts_at ?? row.start_date ?? row.period_start ?? row.effective_date;
    const end = row.ends_at ?? row.end_date ?? row.period_end ?? row.expiry_date;
    if (!start && !end) return '—';
    return [formatDate(start), formatDate(end)].filter(Boolean).join(' → ');
}

const columns = [
    {
        key: 'date_emission',
        label: "Date d'émission",
        getValue: (row) => formatDate(row.printed_at ?? row.issued_at ?? row.created_at),
    },
    {
        key: 'reference',
        label: 'Référence',
        getValue: (row) => row.reference ?? row.id ?? '—',
    },
    {
        key: 'type',
        label: 'Type',
        getValue: (row) => {
            const ct = row.certificate_type;
            const cv = row.certificate_variant;
            if (typeof cv === 'object' && cv?.name) return cv.name;
            if (typeof ct === 'object' && ct) return ct.name ?? ct.code ?? '—';
            return row.type ?? '—';
        },
    },
    {
        key: 'etat',
        label: 'État',
        type: 'badge',
        getValue: (row) => (typeof row.state === 'object' && row.state) ? (row.state.label ?? row.state.name ?? '—') : (row.status ?? row.etat ?? '—'),
        getBadgeClass: (row) => {
            const v = String((typeof row.state === 'object' && row.state) ? (row.state.label ?? row.state.name ?? '') : (row.status ?? row.etat ?? '')).toLowerCase();
            if (['actif', 'active', 'approuvée', 'approuve', 'validé', 'valide'].some((s) => v.includes(s))) return 'bg-emerald-100 text-emerald-800';
            if (['désactivé', 'desactive', 'annulé', 'annule', 'refusé', 'refuse', 'rejeté', 'expiré', 'expire', 'expired'].some((s) => v.includes(s))) return 'bg-red-100 text-red-800';
            return 'bg-slate-100 text-slate-800';
        },
    },
    {
        key: 'assure',
        label: 'Assuré',
        getValue: (row) => row.insured_name ?? row.assure ?? row.insured ?? row.policy_holder ?? '—',
    },
    {
        key: 'plaque',
        label: 'Plaque',
        getValue: (row) => row.licence_plate ?? row.plaque ?? row.registration_number ?? row.immat ?? '—',
    },
    {
        key: 'periode',
        label: 'Période',
        getValue: (row) => formatPeriod(row),
    },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Attestations" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Données issues de la plateforme ASACI. Gestion des attestations / certificats.
        </p>

        <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ error }}
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Liste des attestations</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ list.length }} attestation(s)</p>
                </div>
                <Link href="/digital/attestations" class="text-sm text-slate-600 hover:text-slate-900 font-medium">Actualiser</Link>
            </div>
            <DataTable
                :data="list"
                :columns="columns"
                :row-key="(row) => row.reference ?? row.id ?? row.reference_id"
                empty-message="Aucune attestation pour le moment."
            >
                <template #actions="{ row }">
                    <DataTableAction
                        v-if="row.download_link || row.reference"
                        label="Télécharger PDF"
                        :href="row.download_link || `/digital/attestations/${row.reference}/download`"
                        icon="download"
                        external
                    />
                    <span v-else class="text-slate-400 text-sm">—</span>
                </template>
            </DataTable>
        </div>
    </DashboardLayout>
</template>
