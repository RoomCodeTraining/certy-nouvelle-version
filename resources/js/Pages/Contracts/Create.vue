<script setup>
import { useForm, Link, router } from "@inertiajs/vue3";
import { computed, watch, ref, onMounted, nextTick } from "vue";
import axios from "axios";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import DatePicker from "@/Components/DatePicker.vue";
import { route } from "@/route";
import {
    contractTypeLabel,
    attestationColorLabel,
    attestationColorClasses,
} from "@/utils/contractTypes";

const props = defineProps({
    clients: Array,
    companies: Array,
    contractTypes: Array,
    durationOptions: Array,
    parentContract: { type: Object, default: null },
    typeAssureOptions: { type: Array, default: () => [] },
    vehicleBrands: { type: Array, default: () => [] },
    circulationZones: { type: Array, default: () => [] },
    energySources: { type: Array, default: () => [] },
    vehicleUsages: { type: Array, default: () => [] },
    vehicleTypes: { type: Array, default: () => [] },
    vehicleCategories: { type: Array, default: () => [] },
    vehicleGenders: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Contrats", href: "/contracts" },
    { label: "Nouveau contrat" },
];

const step = ref(1);
const previewLoading = ref(false);
/** Liste clients mutable (pour ajout rapide depuis les panneaux). */
const localClients = ref([]);
function syncClientsFromProps() {
    localClients.value = (props.clients || []).map((c) => ({
        ...c,
        vehicles: (c.vehicles || []).map((v) => ({ ...v })),
    }));
}
onMounted(syncClientsFromProps);
watch(() => props.clients, syncClientsFromProps, { deep: true });

const showClientDrawer = ref(false);
const showVehicleDrawer = ref(false);
const clientQuickForm = ref({
    full_name: "",
    email: "",
    phone: "",
    address: "",
    postal_address: "",
    profession: "",
    type_assure: "",
});
const clientQuickErrors = ref({});
const clientQuickSubmitting = ref(false);
const vehicleQuickForm = ref({
    client_id: "",
    pricing_type: "VP",
    registration_number: "",
    vehicle_brand_id: "",
    vehicle_model_id: "",
    body_type: "",
    color_id: "",
    circulation_zone_id: "",
    energy_source_id: "",
    vehicle_usage_id: "",
    vehicle_type_id: "",
    vehicle_category_id: "",
    vehicle_gender_id: "",
    fiscal_power: null,
    payload_capacity: null,
    engine_capacity: null,
    seat_count: 5,
    year_of_first_registration: null,
    first_registration_date: new Date().toISOString().slice(0, 10),
    registration_card_number: "",
    chassis_number: "",
    new_value: null,
    replacement_value: null,
});
const vehicleQuickErrors = ref({});
const vehicleQuickSubmitting = ref(false);

const recap = ref({
    prime_amount: null,
    accessory_amount: null,
    total_premium: null,
    amounts: {},
});

/** Libellés des garanties de la grille pour le récap */
const guaranteeLabels = {
    base_amount: "Prime de base",
    rc_amount: "RC (Responsabilité civile)",
    defence_appeal_amount: "Défense recours",
    person_transport_amount: "Transport de personnes",
    accessory_amount: "Accessoires",
    taxes_amount: "Taxes",
    cedeao_amount: "CEDEAO",
    fga_amount: "FGA",
};
const guaranteeKeys = Object.keys(guaranteeLabels);

const form = useForm({
    client_id: "",
    vehicle_id: "",
    company_id: "",
    contract_type: "VP",
    parent_id: "",
    status: "draft",
    start_date: "",
    end_date: "",
    duration: "12_months",
    reduction_amount: 0,
    accessory_amount_override: null,
    reduction_bns: null,
    reduction_on_commission: null,
    reduction_on_profession_percent: null,
    reduction_on_profession_amount: null,
    company_accessory: 0,
    agency_accessory: 0,
    commission_amount: 0,
});

const selectedClient = computed(() =>
    localClients.value.find((c) => String(c.id) === String(form.client_id)),
);
const vehiclesForClient = computed(() => selectedClient.value?.vehicles ?? []);
const vehiclesForSelect = computed(() =>
    vehiclesForClient.value.map((v) => ({
        ...v,
        name: v.registration_number || `Sans immat (id ${v.id})`,
    })),
);

function onClientChange() {
    form.vehicle_id = "";
    form.contract_type = "VP";
}

function onVehicleChange() {
    const v = vehiclesForClient.value.find(
        (vh) => String(vh.id) === String(form.vehicle_id),
    );
    form.contract_type = v?.pricing_type ?? "VP";
}

const vehicleQuickModels = computed(() => {
    if (!vehicleQuickForm.value.vehicle_brand_id) return [];
    const brand = props.vehicleBrands.find(
        (b) => String(b.id) === String(vehicleQuickForm.value.vehicle_brand_id),
    );
    return brand?.models ?? [];
});

function openClientDrawer() {
    clientQuickForm.value = {
        full_name: "",
        email: "",
        phone: "",
        address: "",
        postal_address: "",
        profession: "",
        type_assure: "",
    };
    clientQuickErrors.value = {};
    showClientDrawer.value = true;
}

function openVehicleDrawer() {
    vehicleQuickForm.value = {
        client_id: String(form.client_id),
        pricing_type: form.contract_type || "VP",
        registration_number: "",
        vehicle_brand_id: "",
        vehicle_model_id: "",
        body_type: "",
        color_id: "",
        circulation_zone_id: "",
        energy_source_id: "",
        vehicle_usage_id: "",
        vehicle_type_id: "",
        vehicle_category_id: "",
        vehicle_gender_id: "",
        fiscal_power: null,
        payload_capacity: null,
        engine_capacity: null,
        seat_count: 5,
        year_of_first_registration: null,
        first_registration_date: new Date().toISOString().slice(0, 10),
        registration_card_number: "",
        chassis_number: "",
        new_value: null,
        replacement_value: null,
    };
    vehicleQuickErrors.value = {};
    showVehicleDrawer.value = true;
}

function closeClientDrawer() {
    showClientDrawer.value = false;
}

function closeVehicleDrawer() {
    showVehicleDrawer.value = false;
}

async function submitClientQuick() {
    clientQuickErrors.value = {};
    clientQuickSubmitting.value = true;
    try {
        const { data } = await axios.post(
            route("clients.store"),
            clientQuickForm.value,
            {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        const client = data.client;
        if (client) {
            localClients.value.push({
                ...client,
                vehicles: client.vehicles ?? [],
            });
            form.client_id = String(client.id);
            closeClientDrawer();
        }
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            clientQuickErrors.value = err.response.data.errors;
        }
    } finally {
        clientQuickSubmitting.value = false;
    }
}

async function submitVehicleQuick() {
    vehicleQuickErrors.value = {};
    vehicleQuickSubmitting.value = true;
    const payload = { ...vehicleQuickForm.value };
    [
        "fiscal_power",
        "payload_capacity",
        "engine_capacity",
        "seat_count",
        "year_of_first_registration",
        "new_value",
        "replacement_value",
    ].forEach((k) => {
        if (payload[k] === "" || payload[k] == null) payload[k] = null;
        else if (typeof payload[k] === "string" && /^\d+$/.test(payload[k]))
            payload[k] = parseInt(payload[k], 10);
    });
    try {
        const { data } = await axios.post(route("vehicles.store"), payload, {
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        const vehicle = data.vehicle;
        if (vehicle) {
            const client = localClients.value.find(
                (c) => String(c.id) === String(form.client_id),
            );
            if (client) {
                if (!client.vehicles) client.vehicles = [];
                client.vehicles.push({
                    ...vehicle,
                    name:
                        vehicle.registration_number ||
                        `Sans immat (id ${vehicle.id})`,
                });
            }
            form.vehicle_id = String(vehicle.id);
            form.contract_type = vehicle.pricing_type || form.contract_type;
            closeVehicleDrawer();
        }
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            vehicleQuickErrors.value = err.response.data.errors;
        }
    } finally {
        vehicleQuickSubmitting.value = false;
    }
}

function applyParentContract(parent) {
    if (!parent || !parent.id) return;
    form.parent_id = String(parent.id);
    if (parent.client_id) form.client_id = String(parent.client_id);
    if (parent.company_id) form.company_id = String(parent.company_id);
    if (parent.contract_type) form.contract_type = parent.contract_type;
    if (parent.end_date) {
        const end = new Date(parent.end_date + "T12:00:00");
        end.setDate(end.getDate() + 1);
        form.start_date = end.toISOString().slice(0, 10);
        applyDuration();
    }
    // Mettre vehicle_id et lancer le récap après que Vue ait mis à jour
    // vehiclesForSelect (dépend de client_id) pour éviter que le watcher
    // ne vide le récap avec des champs partiels
    nextTick(() => {
        if (parent.vehicle_id) form.vehicle_id = String(parent.vehicle_id);
        nextTick(() => {
            if (
                form.vehicle_id &&
                form.contract_type &&
                form.start_date &&
                form.end_date
            ) {
                fetchPreview();
            }
        });
    });
}

// Pré-remplir le formulaire en cas de renouvellement (parentContract fourni)
// Date d'effet = jour d'expiration de l'ancien contrat + 1 (le lendemain)
onMounted(() => {
    applyParentContract(props.parentContract);
});

// Au cas où parentContract arrive après le mount (hydration Inertia)
watch(
    () => props.parentContract,
    (parent) => applyParentContract(parent),
    { immediate: true },
);

function applyDuration() {
    if (!form.start_date || !form.duration) return;
    const start = new Date(form.start_date);
    const monthsMap = {
        "1_month": 1,
        "3_months": 3,
        "6_months": 6,
        "12_months": 12,
    };
    const months = monthsMap[form.duration] ?? 12;
    const end = new Date(start);
    end.setMonth(end.getMonth() + months);
    end.setDate(end.getDate() - 1);
    form.end_date = end.toISOString().slice(0, 10);
}

const canPreview = computed(
    () =>
        form.vehicle_id &&
        form.contract_type &&
        form.start_date &&
        form.end_date,
);

/** Champs minimaux pour enregistrer en brouillon (étape 1 ou 2). */
const canSaveDraft = computed(
    () =>
        form.client_id &&
        form.vehicle_id &&
        form.company_id &&
        form.contract_type &&
        form.start_date &&
        form.end_date,
);

const PREVIEW_LOADER_MIN_MS = 3000;

async function fetchPreview() {
    if (!canPreview.value) {
        recap.value = {
            prime_amount: null,
            accessory_amount: null,
            total_premium: null,
            amounts: {},
        };
        return;
    }
    previewLoading.value = true;
    const startAt = Date.now();
    try {
        const { data } = await axios.post(
            route("contracts.preview"),
            {
                vehicle_id: form.vehicle_id,
                contract_type: form.contract_type,
                start_date: form.start_date,
                end_date: form.end_date,
            },
            {
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        recap.value = {
            prime_amount: data.prime_amount,
            accessory_amount: data.accessory_amount,
            total_premium: data.total_premium,
            amounts: data.amounts ?? {},
        };
    } catch {
        recap.value = {
            prime_amount: null,
            accessory_amount: null,
            total_premium: null,
            amounts: {},
        };
    } finally {
        const elapsed = Date.now() - startAt;
        const remaining = Math.max(0, PREVIEW_LOADER_MIN_MS - elapsed);
        if (remaining > 0) {
            setTimeout(() => {
                previewLoading.value = false;
            }, remaining);
        } else {
            previewLoading.value = false;
        }
    }
}

/** Accessoire issu de la grille (non modifiable) */
const displayAccessory = computed(() => recap.value.accessory_amount ?? 0);

const companyAccessoryDisplay = computed(
    () => Number(form.company_accessory) || 0,
);
const agencyAccessoryDisplay = computed(
    () => Number(form.agency_accessory) || 0,
);
/** Total accessoires = grille (ou override) + compagnie + agence */
const totalAccessoryDisplay = computed(
    () =>
        displayAccessory.value +
        companyAccessoryDisplay.value +
        agencyAccessoryDisplay.value,
);

/** Montant total avant toute réduction (prime + accessoires) = base du calcul des réductions */
const totalBeforeReduction = computed(
    () => (recap.value.prime_amount ?? 0) + totalAccessoryDisplay.value,
);

/** Réductions en montant (FCFA) appliquées au total avant réduction */
const reductionBnsAmount = computed(() => {
    const pct = Number(form.reduction_bns);
    if (!pct || pct <= 0) return 0;
    return Math.round(totalBeforeReduction.value * (pct / 100));
});
const reductionCommissionAmount = computed(() => {
    const pct = Number(form.reduction_on_commission);
    if (!pct || pct <= 0) return 0;
    return Math.round(totalBeforeReduction.value * (pct / 100));
});
const reductionProfessionPercentAmount = computed(() => {
    const pct = Number(form.reduction_on_profession_percent);
    if (!pct || pct <= 0) return 0;
    return Math.round(totalBeforeReduction.value * (pct / 100));
});
const reductionProfessionAmount = computed(
    () => Number(form.reduction_on_profession_amount) || 0,
);
const reductionOtherAmount = computed(() => Number(form.reduction_amount) || 0);

/** Total de toutes les réductions (impact sur le montant total et les frais) */
const totalReduction = computed(
    () =>
        reductionBnsAmount.value +
        reductionCommissionAmount.value +
        reductionProfessionPercentAmount.value +
        reductionProfessionAmount.value +
        reductionOtherAmount.value,
);

/** Commission (FCFA) — ajoutée au total */
const commissionDisplay = computed(() => Number(form.commission_amount) || 0);

/** Total à payer = Prime TTC + Commission - Réductions */
const displayTotal = computed(() =>
    Math.max(
        0,
        totalBeforeReduction.value +
            commissionDisplay.value -
            totalReduction.value,
    ),
);

watch(
    () => [form.vehicle_id, form.contract_type, form.start_date, form.end_date],
    () => {
        fetchPreview();
    },
    { deep: true },
);

watch(
    () => [form.duration, form.start_date],
    () => {
        applyDuration();
    },
    { deep: true },
);

const inputClass =
    "w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass =
    "border-red-400 focus:border-red-400 focus:ring-red-400";

function submitValidate() {
    form.status = "validated";
    form.transform((data) => ({
        ...data,
        // Toujours envoyer parent_id en renouvellement pour que le contrat soit bien lié
        parent_id:
            data.parent_id ||
            (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route("contracts.store"), { preserveScroll: true });
}

function submitDraft() {
    form.status = "draft";
    form.transform((data) => ({
        ...data,
        parent_id:
            data.parent_id ||
            (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route("contracts.store"), { preserveScroll: true });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Nouveau contrat" />
        </template>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Formulaire principal -->
            <form
                class="flex-1 space-y-6"
                @submit.prevent="step === 1 ? (step = 2) : submitValidate()"
            >
                <!-- Wizard : une seule étape visible à la fois -->
                <!-- Étape 1 : Client, véhicule, période, compagnie -->
                <div
                    v-show="step === 1"
                    class="rounded-xl border border-slate-200 bg-white p-6 space-y-4"
                >
                    <h2
                        class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2"
                    >
                        Étape 1 — Client, véhicule & couverture
                    </h2>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Client *</label
                        >
                        <SearchableSelect
                            v-model="form.client_id"
                            :options="localClients"
                            value-key="id"
                            label-key="full_name"
                            placeholder="Sélectionner un client"
                            :required="true"
                            :error="!!form.errors.client_id"
                            :input-class="inputClass"
                            search-placeholder="Rechercher un client…"
                            @change="onClientChange"
                        />
                        <p
                            v-if="form.errors.client_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.client_id }}
                        </p>
                        <button
                            type="button"
                            class="mt-2 text-sm font-medium text-slate-600 hover:text-slate-900 inline-flex items-center gap-1"
                            @click="openClientDrawer"
                        >
                            + Nouveau client
                        </button>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Véhicule *</label
                        >
                        <SearchableSelect
                            v-model="form.vehicle_id"
                            :options="vehiclesForSelect"
                            value-key="id"
                            label-key="name"
                            placeholder="Choisir un véhicule du client"
                            :required="true"
                            :error="!!form.errors.vehicle_id"
                            :input-class="inputClass"
                            :disabled="!form.client_id"
                            search-placeholder="Rechercher…"
                            @change="onVehicleChange"
                        />
                        <p
                            v-if="form.errors.vehicle_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.vehicle_id }}
                        </p>
                        <p
                            v-if="form.client_id && !vehiclesForClient.length"
                            class="mt-1 text-sm text-amber-600"
                        >
                            Ce client n'a aucun véhicule.
                        </p>
                        <button
                            v-if="form.client_id"
                            type="button"
                            class="mt-2 text-sm font-medium text-slate-600 hover:text-slate-900 inline-flex items-center gap-1"
                            @click="openVehicleDrawer"
                        >
                            + Nouveau véhicule
                        </button>
                        <p
                            v-if="form.vehicle_id && form.contract_type"
                            class="mt-2 flex items-center gap-2"
                        >
                            <span class="text-xs text-slate-500"
                                >Type :
                                {{ contractTypeLabel(form.contract_type) }} —
                                Attestation :</span
                            >
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                    attestationColorClasses(form.contract_type),
                                ]"
                            >
                                {{ attestationColorLabel(form.contract_type) }}
                            </span>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Période de couverture *</label
                            >
                            <select
                                v-model="form.duration"
                                :class="inputClass"
                                @change="applyDuration"
                            >
                                <option
                                    v-for="opt in durationOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Date d'effet *</label
                            >
                            <DatePicker
                                v-model="form.start_date"
                                placeholder="Sélectionner une date"
                                :error="!!form.errors.start_date"
                                :input-class="inputClass"
                                :year-range="[2020, 2030]"
                            />
                            <p
                                v-if="form.errors.start_date"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.start_date }}
                            </p>
                        </div>
                    </div>
                    <div v-if="form.end_date">
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Date d'échéance</label
                        >
                        <p class="text-sm font-medium text-slate-900">
                            {{ form.end_date }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Calculée automatiquement selon la période et la date
                            d'effet.
                        </p>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Compagnie *</label
                        >
                        <SearchableSelect
                            v-model="form.company_id"
                            :options="companies"
                            value-key="id"
                            label-key="name"
                            placeholder="Sélectionner une compagnie"
                            :required="true"
                            :error="!!form.errors.company_id"
                            :input-class="inputClass"
                            search-placeholder="Rechercher une compagnie…"
                        />
                        <p
                            v-if="form.errors.company_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.company_id }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                            @click="step = 2"
                        >
                            Suivant
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50"
                            :disabled="!canSaveDraft || form.processing"
                            @click="submitDraft"
                        >
                            {{
                                form.processing
                                    ? "Enregistrement…"
                                    : "Enregistrer en brouillon"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Étape 2 : Accessoires et réductions (remplace l’étape 1) -->
                <div
                    v-show="step === 2"
                    class="rounded-xl border border-slate-200 bg-white p-6 space-y-4"
                >
                    <h2
                        class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2"
                    >
                        Étape 2 — Accessoires & réductions
                    </h2>
                    <p class="text-xs text-slate-500">
                        Accessoires compagnie et agence (FCFA). Réductions
                        ci‑dessous.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Accessoires compagnie (FCFA)</label
                            >
                            <input
                                v-model.number="form.company_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[
                                    inputClass,
                                    form.errors.company_accessory &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="form.errors.company_accessory"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.company_accessory }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Accessoires agence (FCFA)</label
                            >
                            <input
                                v-model.number="form.agency_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[
                                    inputClass,
                                    form.errors.agency_accessory &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="form.errors.agency_accessory"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.agency_accessory }}
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-slate-700 pt-2">
                        Réductions
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction BNS</label
                            >
                            <input
                                v-model.number="form.reduction_bns"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors.reduction_bns &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="form.errors.reduction_bns"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.reduction_bns }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction sur commission</label
                            >
                            <input
                                v-model.number="form.reduction_on_commission"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors.reduction_on_commission &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="form.errors.reduction_on_commission"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.reduction_on_commission }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction profession</label
                            >
                            <input
                                v-model.number="
                                    form.reduction_on_profession_percent
                                "
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors
                                        .reduction_on_profession_percent &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="
                                    form.errors.reduction_on_profession_percent
                                "
                                class="mt-1 text-sm text-red-600"
                            >
                                {{
                                    form.errors.reduction_on_profession_percent
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                            @click="step = 1"
                        >
                            ← Précédent
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Validation…"
                                    : "Valider le contrat"
                            }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50"
                            :disabled="form.processing"
                            @click="submitDraft"
                        >
                            Enregistrer en brouillon
                        </button>
                    </div>
                </div>
            </form>

            <!-- Récap à droite (largeur élargie pour que les montants tiennent sur une ligne) -->
            <aside class="lg:w-[28rem] shrink-0">
                <div
                    class="rounded-xl border border-slate-200 bg-slate-50 p-5 sticky top-4 space-y-4"
                >
                    <h3 class="text-sm font-semibold text-slate-800">
                        Récapitulatif prime
                    </h3>
                    <template v-if="previewLoading">
                        <div
                            class="flex flex-col items-center justify-center py-8 gap-3"
                        >
                            <div
                                class="w-10 h-10 border-2 border-slate-300 border-t-slate-700 rounded-full animate-spin"
                                aria-hidden="true"
                            />
                            <p class="text-sm text-slate-500">
                                Calcul en cours…
                            </p>
                        </div>
                    </template>
                    <template v-else-if="recap.total_premium != null">
                        <!-- Garanties et montants (grille) -->
                        <div
                            v-if="
                                guaranteeKeys.some(
                                    (k) => recap.amounts[k] != null,
                                )
                            "
                            class="space-y-2"
                        >
                            <h4
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide"
                            >
                                Garanties et montants (grille)
                            </h4>
                            <dl class="space-y-1.5 text-sm">
                                <div
                                    v-for="key in guaranteeKeys"
                                    :key="key"
                                    v-show="recap.amounts[key] != null"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600 truncate">
                                        {{ guaranteeLabels[key] }}
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                    >
                                        {{
                                            Number(
                                                recap.amounts[key],
                                            ).toLocaleString("fr-FR")
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <dl
                            class="space-y-2 text-sm border-t border-slate-200 pt-3"
                        >
                            <div class="flex justify-between gap-2">
                                <dt class="text-slate-600 min-w-0">
                                    Montant prime
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        (
                                            recap.prime_amount ?? 0
                                        ).toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="companyAccessoryDisplay > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600 min-w-0">
                                    Accessoire compagnie
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        companyAccessoryDisplay.toLocaleString(
                                            "fr-FR",
                                        )
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="agencyAccessoryDisplay > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600 min-w-0">
                                    Accessoire agence
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        agencyAccessoryDisplay.toLocaleString(
                                            "fr-FR",
                                        )
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                class="flex justify-between gap-2 pt-2 border-t border-slate-200"
                            >
                                <dt class="font-medium text-slate-700 min-w-0">
                                    Prime TTC
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        totalBeforeReduction.toLocaleString(
                                            "fr-FR",
                                        )
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <template v-if="totalReduction > 0">
                                <div
                                    v-if="reductionBnsAmount > 0"
                                    class="flex justify-between text-red-600"
                                >
                                    <dt class="text-slate-600">
                                        (%) Réduction BNS
                                    </dt>
                                    <dd class="font-medium">
                                        −
                                        {{
                                            reductionBnsAmount.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                                <div
                                    v-if="reductionCommissionAmount > 0"
                                    class="flex justify-between text-red-600"
                                >
                                    <dt class="text-slate-600">
                                        (%) Réduction commission
                                    </dt>
                                    <dd class="font-medium">
                                        −
                                        {{
                                            reductionCommissionAmount.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                                <div
                                    v-if="reductionProfessionPercentAmount > 0"
                                    class="flex justify-between text-red-600"
                                >
                                    <dt class="text-slate-600">
                                        (%) Réduction profession
                                    </dt>
                                    <dd class="font-medium">
                                        −
                                        {{
                                            reductionProfessionPercentAmount.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                                <div
                                    class="flex justify-between gap-2 font-medium text-red-600"
                                >
                                    <dt class="min-w-0">Total réductions</dt>
                                    <dd class="shrink-0 whitespace-nowrap">
                                        −
                                        {{
                                            totalReduction.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                            </template>
                            <div
                                class="flex justify-between gap-2 pt-2 border-t border-slate-200"
                            >
                                <dt class="font-medium text-slate-800 min-w-0">
                                    Total (TTC − réductions)
                                </dt>
                                <dd
                                    class="font-semibold text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{ displayTotal.toLocaleString("fr-FR") }}
                                    FCFA
                                </dd>
                            </div>
                        </dl>
                    </template>
                    <template v-else>
                        <p class="text-sm text-slate-500">
                            Sélectionnez un client, un véhicule, la période et
                            les dates pour voir le montant.
                        </p>
                    </template>
                </div>
            </aside>
        </div>

        <!-- Panneau latéral : Nouveau client -->
        <Teleport to="body">
            <div
                v-if="showClientDrawer"
                class="fixed inset-0 z-50 flex"
                aria-modal="true"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 transition-opacity"
                    aria-hidden="true"
                    @click="closeClientDrawer"
                />
                <div
                    class="fixed right-0 top-0 bottom-0 w-full max-w-md bg-white border-l border-slate-200 flex flex-col shadow-lg overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-200"
                    >
                        <h3 class="text-base font-semibold text-slate-900">
                            Nouveau client
                        </h3>
                        <button
                            type="button"
                            class="p-2 text-slate-500 hover:text-slate-700 rounded-lg"
                            @click="closeClientDrawer"
                        >
                            <span class="sr-only">Fermer</span>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <form
                        class="flex-1 overflow-auto p-5 space-y-4"
                        @submit.prevent="submitClientQuick"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Nom complet *</label
                            >
                            <input
                                v-model="clientQuickForm.full_name"
                                type="text"
                                required
                                :class="[
                                    inputClass,
                                    clientQuickErrors.full_name &&
                                        inputErrorClass,
                                ]"
                                placeholder="Nom du client"
                            />
                            <p
                                v-if="clientQuickErrors.full_name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.full_name[0] }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Email</label
                                >
                                <input
                                    v-model="clientQuickForm.email"
                                    type="email"
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.email &&
                                            inputErrorClass,
                                    ]"
                                />
                                <p
                                    v-if="clientQuickErrors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.email[0] }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Téléphone</label
                                >
                                <input
                                    v-model="clientQuickForm.phone"
                                    type="text"
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.phone &&
                                            inputErrorClass,
                                    ]"
                                />
                                <p
                                    v-if="clientQuickErrors.phone"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.phone[0] }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Adresse</label
                            >
                            <input
                                v-model="clientQuickForm.address"
                                type="text"
                                :class="[
                                    inputClass,
                                    clientQuickErrors.address &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="clientQuickErrors.address"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.address[0] }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Adresse postale</label
                            >
                            <input
                                v-model="clientQuickForm.postal_address"
                                type="text"
                                :class="[
                                    inputClass,
                                    clientQuickErrors.postal_address &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="clientQuickErrors.postal_address"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.postal_address[0] }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Profession *</label
                                >
                                <input
                                    v-model="clientQuickForm.profession"
                                    type="text"
                                    required
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.profession &&
                                            inputErrorClass,
                                    ]"
                                    placeholder="Profession"
                                />
                                <p
                                    v-if="clientQuickErrors.profession"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.profession[0] }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Type assuré</label
                                >
                                <select
                                    v-model="clientQuickForm.type_assure"
                                    :class="inputClass"
                                >
                                    <option value="">—</option>
                                    <option
                                        v-for="opt in typeAssureOptions || []"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-slate-200">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                :disabled="clientQuickSubmitting"
                            >
                                {{
                                    clientQuickSubmitting
                                        ? "Création…"
                                        : "Créer"
                                }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                                @click="closeClientDrawer"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Panneau latéral : Nouveau véhicule -->
        <Teleport to="body">
            <div
                v-if="showVehicleDrawer"
                class="fixed inset-0 z-50 flex"
                aria-modal="true"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 transition-opacity"
                    aria-hidden="true"
                    @click="closeVehicleDrawer"
                />
                <div
                    class="fixed right-0 top-0 bottom-0 w-full max-w-2xl bg-white border-l border-slate-200 flex flex-col shadow-lg overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-200"
                    >
                        <h3 class="text-base font-semibold text-slate-900">
                            Nouveau véhicule
                        </h3>
                        <button
                            type="button"
                            class="p-2 text-slate-500 hover:text-slate-700 rounded-lg"
                            @click="closeVehicleDrawer"
                        >
                            <span class="sr-only">Fermer</span>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <form
                        class="flex-1 overflow-auto p-5 space-y-4"
                        @submit.prevent="submitVehicleQuick"
                    >
                        <p class="text-sm text-slate-600">
                            Client :
                            <strong>{{ selectedClient?.full_name }}</strong>
                        </p>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Informations générales
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Type *</label
                                    >
                                    <select
                                        v-model="vehicleQuickForm.pricing_type"
                                        :class="inputClass"
                                        required
                                        @change="
                                            vehicleQuickForm.vehicle_model_id =
                                                ''
                                        "
                                    >
                                        <option value="VP">VP</option>
                                        <option value="TPC">TPC</option>
                                        <option value="TPM">TPM</option>
                                        <option value="TWO_WHEELER">
                                            Deux roues
                                        </option>
                                    </select>
                                    <p
                                        v-if="vehicleQuickErrors.pricing_type"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.pricing_type[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Immatriculation *</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.registration_number
                                        "
                                        type="text"
                                        required
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.registration_number &&
                                                inputErrorClass,
                                        ]"
                                        placeholder="Numéro d'immatriculation"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.registration_number
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .registration_number[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Marque *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_brand_id
                                        "
                                        :class="inputClass"
                                        required
                                        @change="
                                            vehicleQuickForm.vehicle_model_id =
                                                ''
                                        "
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="b in vehicleBrands || []"
                                            :key="b.id"
                                            :value="b.id"
                                        >
                                            {{ b.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_brand_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_brand_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Modèle *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_model_id
                                        "
                                        :class="inputClass"
                                        required
                                        :disabled="
                                            !vehicleQuickForm.vehicle_brand_id
                                        "
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="m in vehicleQuickModels"
                                            :key="m.id"
                                            :value="m.id"
                                        >
                                            {{ m.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_model_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_model_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Carrosserie *</label
                                    >
                                    <input
                                        v-model="vehicleQuickForm.body_type"
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.body_type &&
                                                inputErrorClass,
                                        ]"
                                        placeholder="Ex. Berline, SUV"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.body_type"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.body_type[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Couleur *</label
                                    >
                                    <select
                                        v-model="vehicleQuickForm.color_id"
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="c in colors || []"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="vehicleQuickErrors.color_id"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.color_id[0] }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Spécifications techniques
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type ===
                                            'TPC' ||
                                        vehicleQuickForm.pricing_type === 'TPM'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Charge utile (tonne) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.payload_capacity
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.payload_capacity &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.payload_capacity
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .payload_capacity[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type === 'VP'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Énergie *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.energy_source_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="e in energySources || []"
                                            :key="e.id"
                                            :value="e.id"
                                        >
                                            {{ e.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.energy_source_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .energy_source_id[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type ===
                                        'TWO_WHEELER'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Cylindrée (cm³) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.engine_capacity
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.engine_capacity &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.engine_capacity
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .engine_capacity[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Nombre de places *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.seat_count
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.seat_count &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.seat_count"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.seat_count[0] }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Classification
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Usage *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_usage_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="u in vehicleUsages || []"
                                            :key="u.id"
                                            :value="u.id"
                                        >
                                            {{ u.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_usage_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_usage_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Catégorie *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_category_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="cat in vehicleCategories ||
                                            []"
                                            :key="cat.id"
                                            :value="cat.id"
                                        >
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_category_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_category_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Genre *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_gender_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="g in vehicleGenders || []"
                                            :key="g.id"
                                            :value="g.id"
                                        >
                                            {{ g.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_gender_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_gender_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Type</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_type_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="t in vehicleTypes || []"
                                            :key="t.id"
                                            :value="t.id"
                                        >
                                            {{ t.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_type_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_type_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Zone de circulation</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.circulation_zone_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="z in circulationZones || []"
                                            :key="z.id"
                                            :value="z.id"
                                        >
                                            {{ z.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.circulation_zone_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .circulation_zone_id[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Informations techniques
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Date 1ère mise en circulation *</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.first_registration_date
                                        "
                                        type="date"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.first_registration_date &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.first_registration_date
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .first_registration_date[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type === 'VP'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Puissance fiscale (CV) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.fiscal_power
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.fiscal_power &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.fiscal_power"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.fiscal_power[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Numéro de carte grise</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.registration_card_number
                                        "
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.registration_card_number &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.registration_card_number
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .registration_card_number[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Numéro de châssis</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.chassis_number
                                        "
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.chassis_number &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.chassis_number"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors.chassis_number[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Valeurs financières
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Valeur neuve</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.new_value
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.new_value &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.new_value"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.new_value[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Valeur de remplacement</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.replacement_value
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.replacement_value &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.replacement_value
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .replacement_value[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex gap-3 pt-4 border-t border-slate-200">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                :disabled="vehicleQuickSubmitting"
                            >
                                {{
                                    vehicleQuickSubmitting
                                        ? "Création…"
                                        : "Créer"
                                }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                                @click="closeVehicleDrawer"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <div class="mt-4">
            <Link
                :href="route('contracts.index')"
                class="text-sm text-slate-600 hover:text-slate-900"
                >← Retour à la liste</Link
            >
        </div>
    </DashboardLayout>
</template>
