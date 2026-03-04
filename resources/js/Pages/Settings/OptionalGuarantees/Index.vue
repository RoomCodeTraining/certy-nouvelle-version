<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps({
    guarantees: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Paramètres", href: "/settings/profile" },
    { label: "Garanties optionnelles" },
];

const baseOptions = [
    { value: "new", label: "Valeur neuve" },
    { value: "venale", label: "Valeur vénale" },
];

const forms = computed(() =>
    props.guarantees.reduce((acc, g) => {
        acc[g.id] = useForm({
            label: g.label,
            rate: Number(g.rate),
            base: g.base,
            enabled: Boolean(g.enabled),
            sort_order: g.sort_order ?? 0,
        });
        return acc;
    }, {}),
);

const inputClass =
    "w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass = "border-red-400 focus:border-red-400 focus:ring-red-400";
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Garanties optionnelles" />
        </template>

        <div class="min-h-[80vh] flex flex-col w-full">
            <p class="text-slate-600 text-sm mb-4">
                Configurer les garanties optionnelles disponibles lors de la création d'un
                contrat. Ces garanties sont proposées au producteur sur l'écran de
                souscription et calculées en pourcentage de la valeur du véhicule.
            </p>
            <p class="text-xs text-slate-500 mb-6">
                Seul l'administrateur principal (root) peut modifier ces paramètres.
            </p>

            <div class="flex-1 w-full">
                <div
                    class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100"
                >
                    <div
                        v-for="g in guarantees"
                        :key="g.id"
                        class="p-4 md:p-5 flex flex-col gap-3 md:flex-row md:items-center md:gap-6"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="
                                        g.enabled
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-slate-50 text-slate-500 border border-slate-200'
                                    "
                                >
                                    {{ g.code }}
                                </span>
                                <span
                                    v-if="g.enabled"
                                    class="text-xs text-emerald-700 font-medium"
                                >
                                    Actif
                                </span>
                                <span v-else class="text-xs text-slate-500 font-medium">
                                    Désactivé
                                </span>
                            </div>
                            <p class="text-sm font-medium text-slate-900 truncate">
                                {{ g.label }}
                            </p>
                        </div>

                        <form
                            v-if="forms[g.id]"
                            class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end"
                            @submit.prevent="
                                forms[g.id].put(
                                    `/settings/guarantees/${g.id}`,
                                    { preserveScroll: true },
                                )
                            "
                        >
                            <div class="sm:col-span-2">
                                <label
                                    class="block text-xs font-medium text-slate-600 mb-1"
                                >
                                    Libellé
                                </label>
                                <input
                                    v-model="forms[g.id].label"
                                    type="text"
                                    :class="[
                                        inputClass,
                                        forms[g.id].errors.label && inputErrorClass,
                                    ]"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-600 mb-1"
                                >
                                    Taux (%)
                                </label>
                                <input
                                    v-model.number="forms[g.id].rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="[
                                        inputClass,
                                        forms[g.id].errors.rate && inputErrorClass,
                                    ]"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-600 mb-1"
                                >
                                    Base
                                </label>
                                <select
                                    v-model="forms[g.id].base"
                                    :class="[
                                        inputClass,
                                        forms[g.id].errors.base && inputErrorClass,
                                    ]"
                                >
                                    <option
                                        v-for="opt in baseOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="sm:col-span-1">
                                <label
                                    class="block text-xs font-medium text-slate-600 mb-1"
                                >
                                    Ordre
                                </label>
                                <input
                                    v-model.number="forms[g.id].sort_order"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :class="inputClass"
                                />
                            </div>
                            <div class="sm:col-span-1 flex items-center gap-3 mt-1">
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input
                                        v-model="forms[g.id].enabled"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    />
                                    <span>Activer</span>
                                </label>
                            </div>
                            <div class="sm:col-span-2 flex items-center gap-3 mt-2">
                                <button
                                    type="submit"
                                    :disabled="forms[g.id].processing"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-70"
                                >
                                    Enregistrer
                                </button>
                                <p
                                    v-if="Object.keys(forms[g.id].errors).length"
                                    class="text-xs text-red-600"
                                >
                                    Corrigez les erreurs avant d'enregistrer.
                                </p>
                            </div>
                        </form>
                    </div>
                    <div v-if="!guarantees.length" class="p-6 text-sm text-slate-500">
                        Aucune garantie optionnelle configurée.
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

