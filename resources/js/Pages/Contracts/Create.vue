<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import { computed, watch, ref, onMounted } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { route } from '@/route';
import { contractTypeLabel, attestationColorLabel, attestationColorClasses } from '@/utils/contractTypes';

const props = defineProps({
    clients: Array,
    companies: Array,
    contractTypes: Array,
    durationOptions: Array,
    parentContract: { type: Object, default: null },
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Contrats', href: '/contracts' },
    { label: 'Nouveau contrat' },
];

const step = ref(1);
const previewLoading = ref(false);
const recap = ref({
    prime_amount: null,
    accessory_amount: null,
    total_premium: null,
    amounts: {},
});

/** Libellés des garanties de la grille pour le récap */
const guaranteeLabels = {
    base_amount: 'Prime de base',
    rc_amount: 'RC (Responsabilité civile)',
    defence_appeal_amount: 'Défense recours',
    person_transport_amount: 'Transport de personnes',
    accessory_amount: 'Accessoires',
    taxes_amount: 'Taxes',
    cedeao_amount: 'CEDEAO',
    fga_amount: 'FGA',
};
const guaranteeKeys = Object.keys(guaranteeLabels);

const form = useForm({
    client_id: '',
    vehicle_id: '',
    company_id: '',
    contract_type: 'VP',
    parent_id: '',
    status: 'draft',
    start_date: '',
    end_date: '',
    duration: '12_months',
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

const selectedClient = computed(() => props.clients?.find(c => String(c.id) === String(form.client_id)));
const vehiclesForClient = computed(() => selectedClient.value?.vehicles ?? []);
const vehiclesForSelect = computed(() =>
    vehiclesForClient.value.map(v => ({
        ...v,
        name: v.registration_number || `Sans immat (id ${v.id})`,
    }))
);

function onClientChange() {
    form.vehicle_id = '';
    form.contract_type = 'VP';
}

function onVehicleChange() {
    const v = vehiclesForClient.value.find(vh => String(vh.id) === String(form.vehicle_id));
    form.contract_type = v?.pricing_type ?? 'VP';
}

function applyParentContract(parent) {
    if (!parent || !parent.id) return;
    form.parent_id = String(parent.id);
    if (parent.client_id) form.client_id = String(parent.client_id);
    if (parent.vehicle_id) form.vehicle_id = String(parent.vehicle_id);
    if (parent.company_id) form.company_id = String(parent.company_id);
    if (parent.contract_type) form.contract_type = parent.contract_type;
    if (parent.end_date) {
        const end = new Date(parent.end_date + 'T12:00:00');
        end.setDate(end.getDate() + 1);
        form.start_date = end.toISOString().slice(0, 10);
    }
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
    { immediate: true }
);

function applyDuration() {
    if (!form.start_date || !form.duration) return;
    const start = new Date(form.start_date);
    const monthsMap = { '1_month': 1, '3_months': 3, '6_months': 6, '12_months': 12 };
    const months = monthsMap[form.duration] ?? 12;
    const end = new Date(start);
    end.setMonth(end.getMonth() + months);
    end.setDate(end.getDate() - 1);
    form.end_date = end.toISOString().slice(0, 10);
}

const canPreview = computed(() =>
    form.vehicle_id && form.contract_type && form.start_date && form.end_date
);

const PREVIEW_LOADER_MIN_MS = 3000;

async function fetchPreview() {
    if (!canPreview.value) {
        recap.value = { prime_amount: null, accessory_amount: null, total_premium: null, amounts: {} };
        return;
    }
    previewLoading.value = true;
    const startAt = Date.now();
    try {
        const { data } = await axios.post(route('contracts.preview'), {
            vehicle_id: form.vehicle_id,
            contract_type: form.contract_type,
            start_date: form.start_date,
            end_date: form.end_date,
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        recap.value = {
            prime_amount: data.prime_amount,
            accessory_amount: data.accessory_amount,
            total_premium: data.total_premium,
            amounts: data.amounts ?? {},
        };
    } catch {
        recap.value = { prime_amount: null, accessory_amount: null, total_premium: null, amounts: {} };
    } finally {
        const elapsed = Date.now() - startAt;
        const remaining = Math.max(0, PREVIEW_LOADER_MIN_MS - elapsed);
        if (remaining > 0) {
            setTimeout(() => { previewLoading.value = false; }, remaining);
        } else {
            previewLoading.value = false;
        }
    }
}

/** Accessoire issu de la grille (ou surcharge manuelle) */
const displayAccessory = computed(() => {
    const override = form.accessory_amount_override;
    if (override !== null && override !== '' && Number(override) >= 0) return Number(override);
    return recap.value.accessory_amount ?? 0;
});

const companyAccessoryDisplay = computed(() => Number(form.company_accessory) || 0);
const agencyAccessoryDisplay = computed(() => Number(form.agency_accessory) || 0);
/** Total accessoires = grille (ou override) + compagnie + agence */
const totalAccessoryDisplay = computed(() =>
    displayAccessory.value + companyAccessoryDisplay.value + agencyAccessoryDisplay.value
);

/** Montant total avant toute réduction (prime + accessoires) = base du calcul des réductions */
const totalBeforeReduction = computed(() =>
    (recap.value.prime_amount ?? 0) + totalAccessoryDisplay.value
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
const reductionProfessionAmount = computed(() => Number(form.reduction_on_profession_amount) || 0);
const reductionOtherAmount = computed(() => Number(form.reduction_amount) || 0);

/** Total de toutes les réductions (impact sur le montant total et les frais) */
const totalReduction = computed(() =>
    reductionBnsAmount.value +
    reductionCommissionAmount.value +
    reductionProfessionPercentAmount.value +
    reductionProfessionAmount.value +
    reductionOtherAmount.value
);

/** Commission (FCFA) — ajoutée au total */
const commissionDisplay = computed(() => Number(form.commission_amount) || 0);

/** Total à payer = Prime TTC + Commission - Réductions */
const displayTotal = computed(() =>
    Math.max(0, totalBeforeReduction.value + commissionDisplay.value - totalReduction.value)
);

watch(
    () => [form.vehicle_id, form.contract_type, form.start_date, form.end_date],
    () => { fetchPreview(); },
    { deep: true }
);

watch(
    () => [form.duration, form.start_date],
    () => { applyDuration(); },
    { deep: true }
);

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';

function submitValidate() {
    form.status = 'validated';
    form.transform((data) => ({
        ...data,
        // Toujours envoyer parent_id en renouvellement pour que le contrat soit bien lié
        parent_id: data.parent_id || (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route('contracts.store'), { preserveScroll: true });
}

function submitDraft() {
    form.status = 'draft';
    form.transform((data) => ({
        ...data,
        parent_id: data.parent_id || (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route('contracts.store'), { preserveScroll: true });
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
                <div v-show="step === 1" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2">
                        Étape 1 — Client, véhicule & couverture
                    </h2>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client *</label>
                        <SearchableSelect
                            v-model="form.client_id"
                            :options="clients"
                            value-key="id"
                            label-key="full_name"
                            placeholder="Sélectionner un client"
                            :required="true"
                            :error="!!form.errors.client_id"
                            :input-class="inputClass"
                            search-placeholder="Rechercher un client…"
                            @change="onClientChange"
                        />
                        <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Véhicule *</label>
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
                        <p v-if="form.errors.vehicle_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_id }}</p>
                        <p v-if="form.client_id && !vehiclesForClient.length" class="mt-1 text-sm text-amber-600">
                            Ce client n'a aucun véhicule. Ajoutez-en un depuis sa fiche.
                        </p>
                        <p v-if="form.vehicle_id && form.contract_type" class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-slate-500">Type : {{ contractTypeLabel(form.contract_type) }} — Attestation :</span>
                            <span
                                :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', attestationColorClasses(form.contract_type)]"
                            >
                                {{ attestationColorLabel(form.contract_type) }}
                            </span>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Période de couverture *</label>
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
                            <label class="block text-sm font-medium text-slate-700 mb-1">Date d'effet *</label>
                            <DatePicker
                                v-model="form.start_date"
                                placeholder="Sélectionner une date"
                                :error="!!form.errors.start_date"
                                :input-class="inputClass"
                                :year-range="[2020, 2030]"
                            />
                            <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                        </div>
                    </div>
                    <div v-if="form.end_date">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date d'échéance</label>
                        <p class="text-sm font-medium text-slate-900">{{ form.end_date }}</p>
                        <p class="text-xs text-slate-500">Calculée automatiquement selon la période et la date d'effet.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Compagnie *</label>
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
                        <p v-if="form.errors.company_id" class="mt-1 text-sm text-red-600">{{ form.errors.company_id }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                            @click="step = 2"
                        >
                            Suivant
                        </button>
                    </div>
                </div>

                <!-- Étape 2 : Accessoires et réductions (remplace l’étape 1) -->
                <div v-show="step === 2" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2">
                        Étape 2 — Accessoires & réductions
                    </h2>
                    <p class="text-xs text-slate-500">
                        L’accessoire grille est calculé selon la grille tarifaire (VP/TPC/TPM/2 roues). Vous pouvez le surcharger ci‑dessous et ajouter accessoires compagnie/agence.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Accessoire grille (surcharge, FCFA)</label>
                            <input
                                v-model.number="form.accessory_amount_override"
                                type="number"
                                min="0"
                                step="1"
                                :class="[inputClass, form.errors.accessory_amount_override && inputErrorClass]"
                                placeholder="Optionnel — laisser vide pour garder la grille"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Accessoire compagnie (FCFA)</label>
                            <input
                                v-model.number="form.company_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[inputClass, form.errors.company_accessory && inputErrorClass]"
                            />
                            <p v-if="form.errors.company_accessory" class="mt-1 text-sm text-red-600">{{ form.errors.company_accessory }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Accessoire agence (FCFA)</label>
                            <input
                                v-model.number="form.agency_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[inputClass, form.errors.agency_accessory && inputErrorClass]"
                            />
                            <p v-if="form.errors.agency_accessory" class="mt-1 text-sm text-red-600">{{ form.errors.agency_accessory }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Commission (FCFA)</label>
                            <input
                                v-model.number="form.commission_amount"
                                type="number"
                                min="0"
                                step="1"
                                :class="[inputClass, form.errors.commission_amount && inputErrorClass]"
                            />
                            <p v-if="form.errors.commission_amount" class="mt-1 text-sm text-red-600">{{ form.errors.commission_amount }}</p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-slate-700 pt-2">Réductions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction BNS</label>
                            <input
                                v-model.number="form.reduction_bns"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[inputClass, form.errors.reduction_bns && inputErrorClass]"
                                placeholder="0–100"
                            />
                            <p v-if="form.errors.reduction_bns" class="mt-1 text-sm text-red-600">{{ form.errors.reduction_bns }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction sur commission</label>
                            <input
                                v-model.number="form.reduction_on_commission"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[inputClass, form.errors.reduction_on_commission && inputErrorClass]"
                                placeholder="0–100"
                            />
                            <p v-if="form.errors.reduction_on_commission" class="mt-1 text-sm text-red-600">{{ form.errors.reduction_on_commission }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction profession</label>
                            <input
                                v-model.number="form.reduction_on_profession_percent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[inputClass, form.errors.reduction_on_profession_percent && inputErrorClass]"
                                placeholder="0–100"
                            />
                            <p v-if="form.errors.reduction_on_profession_percent" class="mt-1 text-sm text-red-600">{{ form.errors.reduction_on_profession_percent }}</p>
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
                            {{ form.processing ? 'Validation…' : 'Valider le contrat' }}
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

            <!-- Récap à droite -->
            <aside class="lg:w-80 shrink-0">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 sticky top-4 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-800">Récapitulatif prime</h3>
                    <template v-if="previewLoading">
                        <div class="flex flex-col items-center justify-center py-8 gap-3">
                            <div class="w-10 h-10 border-2 border-slate-300 border-t-slate-700 rounded-full animate-spin" aria-hidden="true" />
                            <p class="text-sm text-slate-500">Calcul en cours…</p>
                        </div>
                    </template>
                    <template v-else-if="recap.total_premium != null">
                        <!-- Garanties et montants (grille) -->
                        <div v-if="guaranteeKeys.some(k => recap.amounts[k] != null)" class="space-y-2">
                            <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Garanties et montants (grille)</h4>
                            <dl class="space-y-1.5 text-sm">
                                <div
                                    v-for="key in guaranteeKeys"
                                    :key="key"
                                    v-show="recap.amounts[key] != null"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600 truncate">{{ guaranteeLabels[key] }}</dt>
                                    <dd class="font-medium text-slate-900 shrink-0">{{ Number(recap.amounts[key]).toLocaleString('fr-FR') }} FCFA</dd>
                                </div>
                            </dl>
                        </div>
                        <dl class="space-y-2 text-sm border-t border-slate-200 pt-3">
                            <div class="flex justify-between">
                                <dt class="text-slate-600">Montant prime</dt>
                                <dd class="font-medium text-slate-900">{{ (recap.prime_amount ?? 0).toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-600">Accessoire grille</dt>
                                <dd class="font-medium text-slate-900">{{ displayAccessory.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <div v-if="companyAccessoryDisplay > 0" class="flex justify-between">
                                <dt class="text-slate-600">Accessoire compagnie</dt>
                                <dd class="font-medium text-slate-900">{{ companyAccessoryDisplay.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <div v-if="agencyAccessoryDisplay > 0" class="flex justify-between">
                                <dt class="text-slate-600">Accessoire agence</dt>
                                <dd class="font-medium text-slate-900">{{ agencyAccessoryDisplay.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-slate-200">
                                <dt class="font-medium text-slate-700">Prime TTC</dt>
                                <dd class="font-medium text-slate-900">{{ totalBeforeReduction.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <div v-if="commissionDisplay > 0" class="flex justify-between">
                                <dt class="text-slate-600">Commission</dt>
                                <dd class="font-medium text-slate-900">+ {{ commissionDisplay.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                            <template v-if="totalReduction > 0">
                                <div v-if="reductionBnsAmount > 0" class="flex justify-between text-red-600">
                                    <dt class="text-slate-600">(%) Réduction BNS</dt>
                                    <dd class="font-medium">− {{ reductionBnsAmount.toLocaleString('fr-FR') }} FCFA</dd>
                                </div>
                                <div v-if="reductionCommissionAmount > 0" class="flex justify-between text-red-600">
                                    <dt class="text-slate-600">(%) Réduction commission</dt>
                                    <dd class="font-medium">− {{ reductionCommissionAmount.toLocaleString('fr-FR') }} FCFA</dd>
                                </div>
                                <div v-if="reductionProfessionPercentAmount > 0" class="flex justify-between text-red-600">
                                    <dt class="text-slate-600">(%) Réduction profession</dt>
                                    <dd class="font-medium">− {{ reductionProfessionPercentAmount.toLocaleString('fr-FR') }} FCFA</dd>
                                </div>
                                <div class="flex justify-between font-medium text-red-600">
                                    <dt>Total réductions</dt>
                                    <dd>− {{ totalReduction.toLocaleString('fr-FR') }} FCFA</dd>
                                </div>
                            </template>
                            <div class="flex justify-between pt-2 border-t border-slate-200">
                                <dt class="font-medium text-slate-800">Total (TTC + commission − réductions)</dt>
                                <dd class="font-semibold text-slate-900">{{ displayTotal.toLocaleString('fr-FR') }} FCFA</dd>
                            </div>
                        </dl>
                    </template>
                    <template v-else>
                        <p class="text-sm text-slate-500">
                            Sélectionnez un client, un véhicule, la période et les dates pour voir le montant.
                        </p>
                    </template>
                </div>
            </aside>
        </div>

        <div class="mt-4">
            <Link :href="route('contracts.index')" class="text-sm text-slate-600 hover:text-slate-900">← Retour à la liste</Link>
        </div>
    </DashboardLayout>
</template>
