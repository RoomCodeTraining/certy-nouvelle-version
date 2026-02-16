<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from '@/route';
import { useConfirm } from '@/Composables/useConfirm';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    stock: [Object, null],
    transactions: { type: Array, default: () => [] },
    error: String,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Stock' },
];

// API: stock = { status, message, data: [ { organization_code, office_code, name, values: [ { certificate_type, details: { attributed, used, available } } ] } ] }
const rawData = computed(() => {
    const s = props.stock;
    if (!s || !Array.isArray(s.data)) return [];
    return s.data;
});

// Flatten: one row per (office + certificate type)
const detailList = computed(() => {
    const rows = [];
    rawData.value.forEach((item) => {
        const values = item.values ?? [];
        values.forEach((v) => {
            const details = v.details ?? {};
            const attributed = Number(details.attributed ?? 0);
            const usedCount = Number(details.used ?? 0);
            const avail = Number(details.available ?? 0);
            rows.push({
                type: v.certificate_type ?? '—',
                company: item.name ?? item.organization_code ?? '—',
                office_code: item.office_code,
                total: attributed,
                used: usedCount,
                available: avail,
                status: avail > 0 ? 'Bon' : 'Épuisé',
            });
        });
    });
    return rows;
});

// Summary: sum over all details
const total = computed(() => detailList.value.reduce((acc, r) => acc + Number(r.total ?? 0), 0));
const used = computed(() => detailList.value.reduce((acc, r) => acc + Number(r.used ?? 0), 0));
const available = computed(() => detailList.value.reduce((acc, r) => acc + Number(r.available ?? 0), 0));

// Répartition par type (breakdown): group by certificate_type
const breakdown = computed(() => {
    const byType = {};
    detailList.value.forEach((r) => {
        const t = r.type ?? '—';
        if (!byType[t]) byType[t] = { type: t, used: 0 };
        byType[t].used += Number(r.used ?? 0);
    });
    const totalUsed = Object.values(byType).reduce((acc, x) => acc + x.used, 0);
    return Object.values(byType).map((x) => ({
        ...x,
        percent: totalUsed ? Math.round((x.used / totalUsed) * 100) : 0,
    }));
});
const totalUsedForBreakdown = computed(() => breakdown.value.reduce((acc, x) => acc + Number(x.used ?? 0), 0));

// Dernière mise à jour
const lastUpdated = computed(() => {
    const s = props.stock;
    if (!s) return null;
    const d = s.last_updated ?? s.updated_at ?? s.date ?? s.updated_at;
    if (!d) return null;
    const str = String(d);
    if (str.length >= 10) return formatDate(str.slice(0, 10));
    return str;
});

// Demandes de stock d'attestations (transactions) depuis le service externe
const transactionsList = computed(() => Array.isArray(props.transactions) ? props.transactions : (props.transactions?.data ?? []));

const detailColumns = [
    { key: 'type', label: 'Type', getValue: (r) => (r.type ?? r.certificate_type ?? '—').toUpperCase() },
    { key: 'company', label: 'Compagnie', getValue: (r) => r.company ?? '—' },
    { key: 'total', label: 'Total', getValue: (r) => r.total ?? '—' },
    { key: 'used', label: 'Utilisé', getValue: (r) => r.used ?? '—' },
    { key: 'available', label: 'Disponible', getValue: (r) => r.available ?? '—' },
    { key: 'trend', label: 'Tendance', getValue: () => '—' },
    {
        key: 'status',
        label: 'Statut',
        type: 'badge',
        getValue: (r) => r.status ?? 'Bon',
        getBadgeClass: (r) => (r.status === 'Épuisé' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800'),
    },
];

// API transaction: reference, quantity, organization{}, office{}, user{}, certificate_type{}, status{ name, label }, type{ name, label }, formatted_created_at
const historyColumns = [
    {
        key: 'date',
        label: 'Date',
        getValue: (r) => r.formatted_created_at ?? formatDate(r.created_at),
    },
    { key: 'reference', label: 'Référence', getValue: (r) => r.reference ?? r.id ?? '—' },
    { key: 'quantity', label: 'Quantité', getValue: (r) => r.quantity ?? '—' },
    {
        key: 'type',
        label: 'Type',
        getValue: (r) => (typeof r.certificate_type === 'object' && r.certificate_type)
            ? (r.certificate_type.name ?? r.certificate_type.code ?? '—')
            : (r.type?.label ?? r.type?.name ?? r.type ?? '—'),
    },
    {
        key: 'status',
        label: 'Statut',
        type: 'badge',
        getValue: (r) => (typeof r.status === 'object' && r.status) ? (r.status.label ?? r.status.name ?? '—') : (r.status ?? r.statut ?? '—'),
        getBadgeClass: (r) => {
            const v = String((typeof r.status === 'object' && r.status) ? (r.status.label ?? r.status.name ?? '') : (r.status ?? r.statut ?? '')).toLowerCase();
            if (['approuvée', 'approuve', 'validé', 'valide', 'approved'].some((s) => v.includes(s))) return 'bg-emerald-100 text-emerald-800';
            if (['rejetée', 'reject', 'refusé', 'refuse', 'annulé', 'annule', 'cancelled', 'expiré', 'expire', 'expired'].some((s) => v.includes(s))) return 'bg-red-100 text-red-800';
            return 'bg-slate-100 text-slate-800';
        },
    },
    {
        key: 'user',
        label: 'Utilisateur',
        getValue: (r) => (typeof r.user === 'object' && r.user?.name) ? r.user.name : (r.user ?? '—'),
    },
    {
        key: 'organization',
        label: 'Organisation',
        getValue: (r) => (typeof r.organization === 'object' && r.organization?.name) ? r.organization.name : (r.organization ?? '—'),
    },
    {
        key: 'office',
        label: 'Bureau',
        getValue: (r) => (typeof r.office === 'object' && r.office?.name) ? r.office.name : (r.office ?? '—'),
    },
];

const { confirm: confirmDialog } = useConfirm();
const txRef = (r) => r.reference ?? r.id;

function cancelTransaction(row) {
    confirmDialog({
        title: 'Annuler la transaction',
        message: 'Voulez-vous annuler cette demande de stock ?',
        confirmLabel: 'Annuler la transaction',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.post(route('digital.transactions.cancel', txRef(row)));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Gestion des Stocks">
                <template #actions>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('digital.stock.create')"
                            class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 rounded-xl sm:rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                        >
                            Demande de stock
                        </Link>
                        <Link
                            :href="route('digital.stock')"
                            class="inline-flex items-center justify-center min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl sm:rounded-lg hover:bg-slate-50"
                        >
                            Actualiser
                        </Link>
                    </div>
                </template>
            </PageHeader>
        </template>

        <p class="text-sm text-slate-600 mb-6">
            Suivi des attestations en stock
        </p>

        <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-sm mb-6">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ error }}
        </div>

        <div v-else class="space-y-8">
            <!-- Cartes récap -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="text-2xl font-bold text-slate-900">{{ total }}</div>
                    <div class="text-sm text-slate-500 mt-1">Stock Total</div>
                    <div class="text-xs text-slate-400 mt-0.5">Total des attestations en stock</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="text-2xl font-bold text-slate-900">{{ used }}</div>
                    <div class="text-sm text-slate-500 mt-1">Utilisé</div>
                    <div class="text-xs text-slate-400 mt-0.5">Attestations utilisées</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="text-2xl font-bold text-slate-900">{{ available }}</div>
                    <div class="text-sm text-slate-500 mt-1">Disponible</div>
                    <div class="text-xs text-slate-400 mt-0.5">Attestations disponibles</div>
                </div>
            </div>

            <!-- Répartition des utilisations -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-900">Répartition des utilisations</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Distribution par type d'attestation</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-4 mb-4">
                        <span class="text-lg font-semibold text-slate-900">{{ totalUsedForBreakdown }}</span>
                        <span class="text-sm text-slate-500">Total utilisé</span>
                    </div>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, i) in breakdown"
                            :key="i"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="font-medium text-slate-700">{{ item.type ?? item.name ?? item.label ?? '—' }}</span>
                            <span class="text-slate-500">({{ item.percent ?? (totalUsedForBreakdown ? Math.round((Number(item.used ?? item.utilise ?? item.count ?? 0) / totalUsedForBreakdown) * 100) : 0) }}%)</span>
                        </li>
                    </ul>
                    <p v-if="!breakdown.length" class="text-sm text-slate-400">Aucune donnée de répartition.</p>
                </div>
            </div>

            <!-- Détail des stocks -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Détail des stocks</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Dernière mise à jour: {{ lastUpdated ?? '—' }}
                        </p>
                    </div>
                    <a
                        href="/digital/stock"
                        class="text-sm text-slate-600 hover:text-slate-900 font-medium"
                    >
                        Exporter
                    </a>
                </div>
                <!-- Mobile : cartes -->
                <div class="md:hidden divide-y divide-slate-100">
                    <div
                        v-for="(r, i) in detailList"
                        :key="`${r.company}-${r.type}-${r.office_code ?? ''}-${i}`"
                        class="p-4"
                    >
                        <p class="font-medium text-slate-900">{{ (r.type ?? '—').toUpperCase() }}</p>
                        <p class="text-sm text-slate-700 mt-0.5">{{ r.company ?? '—' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Total: {{ r.total ?? '—' }} · Utilisé: {{ r.used ?? '—' }} · Disponible: {{ r.available ?? '—' }}</p>
                        <span
                            :class="['inline-flex mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium', r.status === 'Épuisé' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']"
                        >
                            {{ r.status ?? 'Bon' }}
                        </span>
                    </div>
                    <div v-if="!detailList.length" class="py-8 px-4 text-center text-slate-500 text-sm">Aucun détail de stock.</div>
                </div>
                <!-- Desktop : tableau -->
                <div class="hidden md:block">
                    <DataTable
                        :data="detailList"
                        :columns="detailColumns"
                        :row-key="(row) => `${row.company}-${row.type}-${row.office_code ?? ''}`"
                        empty-message="Aucun détail de stock."
                    />
                </div>
            </div>

            <!-- Demandes de stock d'attestations -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-900">Demandes de stock d'attestations</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Historique des demandes de stock d'attestations</p>
                </div>
                <div class="px-4 py-2 border-b border-slate-100 text-sm text-slate-500">
                    {{ transactionsList.length }} demande(s)
                </div>
                <!-- Mobile : cartes -->
                <div class="md:hidden divide-y divide-slate-100">
                    <div
                        v-for="row in transactionsList"
                        :key="row.id ?? row.reference ?? Math.random()"
                        class="p-4"
                    >
                        <p class="text-xs text-slate-500">{{ row.formatted_created_at ?? formatDate(row.created_at) }}</p>
                        <p class="font-medium text-slate-900 mt-0.5">{{ row.reference ?? row.id ?? '—' }}</p>
                        <p class="text-sm text-slate-700">Quantité: {{ row.quantity ?? '—' }} · {{ (typeof row.certificate_type === 'object' && row.certificate_type) ? (row.certificate_type.name ?? row.certificate_type.code ?? '—') : (row.type?.label ?? row.type?.name ?? '—') }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ (typeof row.status === 'object' && row.status) ? (row.status.label ?? row.status.name ?? '—') : (row.status ?? '—') }}</p>
                        <button
                            type="button"
                            class="mt-2 text-sm text-red-600 hover:text-red-700 font-medium"
                            @click="cancelTransaction(row)"
                        >
                            Annuler
                        </button>
                    </div>
                    <div v-if="!transactionsList.length" class="py-8 px-4 text-center text-slate-500 text-sm">Aucune demande de stock.</div>
                </div>
                <!-- Desktop : tableau -->
                <div class="hidden md:block">
                    <DataTable
                        :data="transactionsList"
                        :columns="historyColumns"
                        :row-key="(row) => row.id ?? row.reference ?? String(Math.random())"
                        empty-message="Aucune demande de stock d'attestations."
                    >
                        <template #actions="{ row }">
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:text-red-700 font-medium"
                                @click="cancelTransaction(row)"
                            >
                                Annuler
                            </button>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
