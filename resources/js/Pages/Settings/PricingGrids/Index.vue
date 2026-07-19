<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { route } from "@/route";

const props = defineProps({
    grids: { type: Object, default: () => ({}) },
    enabledTypes: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Paramètres", href: "/settings/profile" },
    { label: "Grille tarifaire" },
];

const pricingGridTab = ref(props.enabledTypes?.[0] ?? "VP");
const amountCols = [
    { key: "base_amount", label: "Base" },
    { key: "rc_amount", label: "RC" },
    { key: "defence_appeal_amount", label: "Recours" },
    { key: "person_transport_amount", label: "Transport" },
    { key: "accessory_amount", label: "Accessoire" },
    { key: "taxes_amount", label: "Taxes" },
    { key: "cedeao_amount", label: "CEDEAO" },
    { key: "cp_amount", label: "Prime ASACI" },
    { key: "fga_amount", label: "FGA" },
];
const savingCell = ref(null);

const currentGridRows = computed(() => {
    if (!props.grids || !pricingGridTab.value) return [];
    return props.grids[pricingGridTab.value] ?? [];
});

async function savePricingCell(type, row, field, value) {
    const isActiveField = field === "is_active";
    const num = isActiveField ? (value ? 1 : 0) : parseInt(value, 10);
    if (!isActiveField && (isNaN(num) || num < 0)) return;
    savingCell.value = { type, id: row.id, field };
    try {
        const body = isActiveField
            ? { type, id: row.id, is_active: !!value }
            : { type, id: row.id, [field]: num };
        const res = await fetch(route("api.pricing-grids.update"), {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") ?? "",
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        if (res.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (_) {
        /* ignore */
    } finally {
        if (
            savingCell.value?.id === row.id &&
            savingCell.value?.field === field
        ) {
            savingCell.value = null;
        }
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Grille tarifaire" />
        </template>

        <div class="min-h-[80vh] flex flex-col w-full">
            <div class="mb-6 space-y-2">
                <h2 class="text-base font-semibold text-slate-900">
                    Modifier les grilles tarifaires
                </h2>
                <p class="text-slate-600 text-sm">
                    Modifiez les montants des grilles VP, TPC, TPM et 2 roues.
                    Cliquez dans une cellule puis validez en sortant du champ (blur).
                    Cliquez sur Oui/Non pour activer ou désactiver une ligne.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="border-b border-slate-200">
                    <div class="flex gap-1 overflow-x-auto px-4">
                        <button
                            v-for="t in enabledTypes"
                            :key="t"
                            type="button"
                            :class="[
                                'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px shrink-0',
                                pricingGridTab === t
                                    ? 'border-slate-900 text-slate-900'
                                    : 'border-transparent text-slate-500 hover:text-slate-700',
                            ]"
                            @click="pricingGridTab = t"
                        >
                            {{ t === "TWO_WHEELER" ? "2 roues" : t }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[800px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    v-if="pricingGridTab === 'VP'"
                                    class="text-left py-2 px-3 font-medium text-slate-600"
                                >
                                    Énergie
                                </th>
                                <th
                                    v-if="
                                        pricingGridTab === 'VP' ||
                                        pricingGridTab === 'TPC' ||
                                        pricingGridTab === 'TPM'
                                    "
                                    class="text-left py-2 px-3 font-medium text-slate-600"
                                >
                                    Durée
                                </th>
                                <th
                                    v-if="
                                        pricingGridTab === 'VP' ||
                                        pricingGridTab === 'TWO_WHEELER'
                                    "
                                    class="text-left py-2 px-3 font-medium text-slate-600"
                                >
                                    {{
                                        pricingGridTab === "VP"
                                            ? "Puissance"
                                            : "Cylindrée"
                                    }}
                                </th>
                                <th
                                    v-if="
                                        pricingGridTab === 'TPC' ||
                                        pricingGridTab === 'TPM'
                                    "
                                    class="text-left py-2 px-3 font-medium text-slate-600"
                                >
                                    Charge utile
                                </th>
                                <th
                                    v-for="col in amountCols"
                                    :key="col.key"
                                    class="text-right py-2 px-3 font-medium text-slate-600"
                                >
                                    {{ col.label }}
                                </th>
                                <th
                                    class="text-center py-2 px-3 font-medium text-slate-600 w-16"
                                >
                                    Actif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="row in currentGridRows"
                                :key="row.id"
                                class="hover:bg-slate-50/50"
                            >
                                <td
                                    v-if="pricingGridTab === 'VP'"
                                    class="py-2 px-3 text-slate-700"
                                >
                                    {{ row.energy_source }}
                                </td>
                                <td
                                    v-if="
                                        pricingGridTab === 'VP' ||
                                        pricingGridTab === 'TPC' ||
                                        pricingGridTab === 'TPM'
                                    "
                                    class="py-2 px-3 text-slate-700"
                                >
                                    {{ row.duration }}
                                </td>
                                <td
                                    v-if="
                                        pricingGridTab === 'VP' ||
                                        pricingGridTab === 'TWO_WHEELER'
                                    "
                                    class="py-2 px-3 text-slate-700"
                                >
                                    {{ row.power_range }}
                                </td>
                                <td
                                    v-if="
                                        pricingGridTab === 'TPC' ||
                                        pricingGridTab === 'TPM'
                                    "
                                    class="py-2 px-3 text-slate-700"
                                >
                                    {{ row.payload_range }}
                                </td>
                                <td
                                    v-for="col in amountCols"
                                    :key="col.key"
                                    class="py-1 px-2"
                                >
                                    <input
                                        type="number"
                                        min="0"
                                        :value="row[col.key]"
                                        class="w-full max-w-[100px] rounded border border-slate-200 px-2 py-1 text-right text-slate-900 text-xs focus:border-slate-400 focus:ring-1 focus:outline-none"
                                        :disabled="
                                            savingCell?.id === row.id &&
                                            savingCell?.field === col.key
                                        "
                                        @blur="
                                            (e) =>
                                                savePricingCell(
                                                    pricingGridTab,
                                                    row,
                                                    col.key,
                                                    e.target.value,
                                                )
                                        "
                                    />
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button
                                        type="button"
                                        :class="[
                                            'inline-flex px-2 py-0.5 rounded text-xs font-medium cursor-pointer',
                                            row.is_active
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                                        ]"
                                        :disabled="
                                            savingCell?.id === row.id &&
                                            savingCell?.field === 'is_active'
                                        "
                                        @click="
                                            savePricingCell(
                                                pricingGridTab,
                                                row,
                                                'is_active',
                                                !row.is_active,
                                            )
                                        "
                                    >
                                        {{ row.is_active ? "Oui" : "Non" }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="currentGridRows.length === 0">
                                <td
                                    :colspan="15"
                                    class="py-8 text-center text-slate-500"
                                >
                                    Aucune ligne dans cette grille.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
