<script setup>
import { computed, ref } from "vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { route } from "@/route";
import { contractTypeLabel } from "@/utils/contractTypes";

const props = defineProps({
    rows: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    period_label: { type: String, default: "" },
    period: { type: Object, default: () => ({ from: null, to: null }) },
    users: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Rapports", href: "/reports/production" },
    { label: "Production contrats" },
];

function formatXOF(value) {
    if (value == null) return "—";
    return (
        new Intl.NumberFormat("fr-FR", {
            style: "decimal",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value) + " F CFA"
    );
}

const totals = computed(() => {
    const rows = props.rows ?? [];
    const reduce = (fn) => rows.reduce((sum, r) => sum + fn(r), 0);
    const totalContracts = reduce((r) => r.contracts_count ?? 0);
    const totalAmount = reduce((r) => r.total_amount ?? 0);
    const totalBefore = reduce((r) => r.total_before_reduction ?? 0);
    const totalReduction = reduce((r) => r.total_reduction_amount ?? 0);
    const avgReductionPct =
        totalBefore > 0 ? Math.round((totalReduction / totalBefore) * 10000) / 100 : 0;

    return {
        totalContracts,
        totalAmount,
        totalBefore,
        totalReduction,
        avgReductionPct,
    };
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (props.filters?.month) params.set("month", props.filters.month);
    if (props.filters?.user_id) params.set("user_id", props.filters.user_id);
    const base = route("reports.production.export");
    const qs = params.toString();
    return qs ? `${base}?${qs}` : base;
});

const isExporting = ref(false);

function handleExportClick() {
    if (isExporting.value) return;
    isExporting.value = true;

    const url = exportUrl.value;

    try {
        const link = document.createElement("a");
        link.href = url;
        link.target = "_blank";
        link.rel = "noopener";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } finally {
        // On laisse un petit délai pour éviter un flash si le téléchargement est très rapide
        setTimeout(() => {
            isExporting.value = false;
        }, 2000);
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Production de contrats par utilisateur">
                <template #subtitle>
                    Période : {{ period_label || "Mois en cours" }} —
                    contrats <span class="font-semibold">actifs</span> avec n° d'attestation.
                </template>
                <template #actions>
                    <button
                        type="button"
                        :disabled="isExporting"
                        @click="handleExportClick"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium bg-white hover:bg-slate-50 disabled:opacity-60 disabled:cursor-wait"
                    >
                        <svg
                            v-if="isExporting"
                            class="w-4 h-4 animate-spin text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <span>Exporter Excel</span>
                    </button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-4 sm:space-y-6">
            <!-- Filtres -->
            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <form
                    action="/reports/production"
                    method="get"
                    class="flex flex-col sm:flex-row sm:items-end gap-4"
                >
                    <div class="flex flex-col gap-1">
                        <label for="month" class="text-xs font-medium text-slate-600 uppercase"
                            >Mois</label
                        >
                        <input
                            id="month"
                            name="month"
                            type="month"
                            :value="filters?.month || ''"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="user_id" class="text-xs font-medium text-slate-600 uppercase"
                            >Utilisateur</label
                        >
                        <select
                            id="user_id"
                            name="user_id"
                            :value="filters?.user_id || ''"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm min-w-[200px] focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        >
                            <option value="">Tous les utilisateurs</option>
                            <option
                                v-for="u in users"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                        >
                            Filtrer
                        </button>
                        <a
                            href="/reports/production"
                            class="inline-flex items-center px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                        >
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </section>

            <!-- Résumé global -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                        Contrats (tous utilisateurs)
                    </p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ totals.totalContracts }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                        Montant total
                    </p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        {{ formatXOF(totals.totalAmount) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                        Total réductions
                    </p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        {{ formatXOF(totals.totalReduction) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                        Réduction moyenne
                    </p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700">
                        {{ totals.avgReductionPct.toFixed(2) }} %
                    </p>
                </div>
            </section>

            <!-- Tableau principal -->
            <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Détail par utilisateur et type
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">
                                    Utilisateur
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-slate-600">
                                    Type
                                </th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">
                                    Nb contrats
                                </th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">
                                    Montant total
                                </th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">
                                    Montant avant réductions
                                </th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">
                                    Total réductions
                                </th>
                                <th class="text-right py-3 px-4 font-medium text-slate-600">
                                    Réduction moyenne
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.user_id ?? row.user_name"
                                class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50"
                            >
                                <td class="py-3 px-4 text-slate-900">
                                    {{ row.user_name }}
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ contractTypeLabel(row.type) }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">
                                    {{ row.contracts_count }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">
                                    {{ formatXOF(row.total_amount) }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">
                                    {{ formatXOF(row.total_before_reduction) }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">
                                    {{ formatXOF(row.total_reduction_amount) }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">
                                    {{ (row.avg_reduction_pct ?? 0).toFixed(2) }} %
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="10" class="py-8 px-4 text-center text-slate-500 text-sm">
                                    Aucun contrat pour les critères sélectionnés.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>

