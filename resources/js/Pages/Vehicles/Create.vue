<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { route } from '@/route';

const props = defineProps({
    clients: { type: Array, default: () => [] },
    client: Object,
    brands: Array,
    circulationZones: { type: Array, default: () => [] },
    energySources: { type: Array, default: () => [] },
    vehicleUsages: { type: Array, default: () => [] },
    vehicleTypes: { type: Array, default: () => [] },
    vehicleCategories: { type: Array, default: () => [] },
    vehicleGenders: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});

const breadcrumbs = computed(() => {
    const base = [
        { label: 'Tableau de bord', href: '/dashboard' },
        { label: 'Véhicules', href: route('vehicles.index') },
        { label: 'Nouveau véhicule' },
    ];
    if (props.client?.id) {
        return [
            { label: 'Tableau de bord', href: '/dashboard' },
            { label: 'Clients', href: '/clients' },
            { label: props.client.full_name, href: route('clients.show', props.client.id) },
            { label: 'Nouveau véhicule' },
        ];
    }
    return base;
});

const pricingTypeOptions = [
    { value: 'VP', label: 'VP (Véhicule Particulier)' },
    { value: 'TPC', label: 'TPC (Transport pour propre compte)' },
    { value: 'TPM', label: 'TPM (Transport Personnes et Marchandises)' },
    { value: 'TWO_WHEELER', label: 'Deux roues' },
];

const form = useForm({
    client_id: props.client?.id ?? '',
    pricing_type: '',
    vehicle_type_id: '',
    registration_number: '',
    vehicle_brand_id: '',
    vehicle_model_id: '',
    body_type: '',
    color_id: '',
    payload_capacity: null,
    energy_source_id: '',
    engine_capacity: null,
    seat_count: null,
    vehicle_usage_id: '',
    vehicle_category_id: '',
    vehicle_gender_id: '',
    circulation_zone_id: '',
    fiscal_power: null,
    year_of_first_registration: null,
    first_registration_date: '',
    registration_card_number: '',
    chassis_number: '',
    new_value: null,
    replacement_value: null,
});

const modelsForBrand = computed(() => {
    if (!form.vehicle_brand_id) return [];
    const brand = props.brands.find(b => String(b.id) === String(form.vehicle_brand_id));
    return brand?.models ?? [];
});

function onBrandChange() {
    form.vehicle_model_id = '';
}

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Nouveau véhicule" />
        </template>

        <div class="min-h-[60vh] flex flex-col w-full max-w-none">
            <form @submit.prevent="form.post(route('vehicles.store'))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-6 w-full">
                <!-- Client -->
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
                    />
                    <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
                </div>

                <!-- Informations générales -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2 w-full">Informations générales</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type *</label>
                            <SearchableSelect
                                v-model="form.pricing_type"
                                :options="pricingTypeOptions"
                                value-key="value"
                                label-key="label"
                                placeholder="Choisir (VP, TPC, TPM, Deux roues)"
                                :required="true"
                                :error="!!form.errors.pricing_type"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.pricing_type" class="mt-1 text-sm text-red-600">{{ form.errors.pricing_type }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Immatriculation *</label>
                            <input
                                v-model="form.registration_number"
                                type="text"
                                :class="[inputClass, form.errors.registration_number && inputErrorClass]"
                                placeholder="Numéro d'immatriculation"
                            />
                            <p v-if="form.errors.registration_number" class="mt-1 text-sm text-red-600">{{ form.errors.registration_number }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Marque *</label>
                            <SearchableSelect
                                v-model="form.vehicle_brand_id"
                                :options="brands"
                                value-key="id"
                                label-key="name"
                                placeholder="Sélectionner une marque"
                                :required="true"
                                :error="!!form.errors.vehicle_brand_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher une marque…"
                                @change="onBrandChange"
                            />
                            <p v-if="form.errors.vehicle_brand_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_brand_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Modèle *</label>
                            <SearchableSelect
                                v-model="form.vehicle_model_id"
                                :options="modelsForBrand"
                                value-key="id"
                                label-key="name"
                                placeholder="Sélectionner un modèle"
                                :required="true"
                                :error="!!form.errors.vehicle_model_id"
                                :input-class="inputClass"
                                :disabled="!form.vehicle_brand_id"
                                search-placeholder="Rechercher un modèle…"
                            />
                            <p v-if="form.errors.vehicle_model_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_model_id }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Carrosserie *</label>
                            <input
                                v-model="form.body_type"
                                type="text"
                                :class="[inputClass, form.errors.body_type && inputErrorClass]"
                                placeholder="Ex. Berline, SUV"
                            />
                            <p v-if="form.errors.body_type" class="mt-1 text-sm text-red-600">{{ form.errors.body_type }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Couleur *</label>
                            <SearchableSelect
                                v-model="form.color_id"
                                :options="colors"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir une couleur"
                                :required="true"
                                :error="!!form.errors.color_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.color_id" class="mt-1 text-sm text-red-600">{{ form.errors.color_id }}</p>
                        </div>
                    </div>
                </fieldset>

                <!-- Spécifications techniques -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2 w-full">Spécifications techniques</legend>
                    <p class="text-xs text-slate-500">Champs affichés selon le type sélectionné : VP → puissance & énergie · TPC/TPM → charge utile · Deux roues → cylindrée.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-if="form.pricing_type === 'TPC' || form.pricing_type === 'TPM'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Charge utile (tonne) *</label>
                            <input
                                v-model.number="form.payload_capacity"
                                type="number"
                                min="0"
                                step="0.01"
                                :class="[inputClass, form.errors.payload_capacity && inputErrorClass]"
                            />
                            <p v-if="form.errors.payload_capacity" class="mt-1 text-sm text-red-600">{{ form.errors.payload_capacity }}</p>
                        </div>
                        <div v-if="form.pricing_type === 'VP'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Énergie *</label>
                            <SearchableSelect
                                v-model="form.energy_source_id"
                                :options="energySources"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :required="form.pricing_type === 'VP'"
                                :error="!!form.errors.energy_source_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.energy_source_id" class="mt-1 text-sm text-red-600">{{ form.errors.energy_source_id }}</p>
                        </div>
                        <div v-if="form.pricing_type === 'TWO_WHEELER'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cylindrée (cm³) *</label>
                            <input
                                v-model.number="form.engine_capacity"
                                type="number"
                                min="0"
                                :class="[inputClass, form.errors.engine_capacity && inputErrorClass]"
                            />
                            <p v-if="form.errors.engine_capacity" class="mt-1 text-sm text-red-600">{{ form.errors.engine_capacity }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de places *</label>
                            <input
                                v-model.number="form.seat_count"
                                type="number"
                                min="0"
                                :class="[inputClass, form.errors.seat_count && inputErrorClass]"
                            />
                            <p v-if="form.errors.seat_count" class="mt-1 text-sm text-red-600">{{ form.errors.seat_count }}</p>
                        </div>
                    </div>
                </fieldset>

                <!-- Classification -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2 w-full">Classification</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Usage *</label>
                            <SearchableSelect
                                v-model="form.vehicle_usage_id"
                                :options="vehicleUsages"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :required="true"
                                :error="!!form.errors.vehicle_usage_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.vehicle_usage_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_usage_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Catégorie *</label>
                            <SearchableSelect
                                v-model="form.vehicle_category_id"
                                :options="vehicleCategories"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :required="true"
                                :error="!!form.errors.vehicle_category_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.vehicle_category_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Genre *</label>
                            <SearchableSelect
                                v-model="form.vehicle_gender_id"
                                :options="vehicleGenders"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :required="true"
                                :error="!!form.errors.vehicle_gender_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.vehicle_gender_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_gender_id }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                            <SearchableSelect
                                v-model="form.vehicle_type_id"
                                :options="vehicleTypes"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :error="!!form.errors.vehicle_type_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Zone de circulation</label>
                            <SearchableSelect
                                v-model="form.circulation_zone_id"
                                :options="circulationZones"
                                value-key="id"
                                label-key="name"
                                placeholder="Choisir"
                                :error="!!form.errors.circulation_zone_id"
                                :input-class="inputClass"
                                search-placeholder="Rechercher…"
                            />
                            <p v-if="form.errors.circulation_zone_id" class="mt-1 text-sm text-red-600">{{ form.errors.circulation_zone_id }}</p>
                        </div>
                    </div>
                </fieldset>

                <!-- Informations techniques -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2 w-full">Informations techniques</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Date 1ère mise en circulation *</label>
                            <DatePicker
                                v-model="form.first_registration_date"
                                placeholder="Sélectionner une date"
                                :error="!!form.errors.first_registration_date"
                                :input-class="inputClass"
                                :year-range="[1990, 2030]"
                            />
                            <p v-if="form.errors.first_registration_date" class="mt-1 text-sm text-red-600">{{ form.errors.first_registration_date }}</p>
                        </div>
                        <div v-if="form.pricing_type === 'VP'" class="max-w-xs">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Puissance fiscale (CV) *</label>
                            <input v-model.number="form.fiscal_power" type="number" min="0" :class="[inputClass, form.errors.fiscal_power && inputErrorClass]" />
                            <p v-if="form.errors.fiscal_power" class="mt-1 text-sm text-red-600">{{ form.errors.fiscal_power }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Numéro de carte grise</label>
                            <input
                                v-model="form.registration_card_number"
                                type="text"
                                :class="[inputClass, form.errors.registration_card_number && inputErrorClass]"
                            />
                            <p v-if="form.errors.registration_card_number" class="mt-1 text-sm text-red-600">{{ form.errors.registration_card_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Numéro de châssis</label>
                            <input
                                v-model="form.chassis_number"
                                type="text"
                                :class="[inputClass, form.errors.chassis_number && inputErrorClass]"
                            />
                            <p v-if="form.errors.chassis_number" class="mt-1 text-sm text-red-600">{{ form.errors.chassis_number }}</p>
                        </div>
                    </div>
                </fieldset>

                <!-- Valeurs financières -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2 w-full">Valeurs financières</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valeur neuve</label>
                            <input
                                v-model.number="form.new_value"
                                type="number"
                                min="0"
                                step="0.01"
                                :class="[inputClass, form.errors.new_value && inputErrorClass]"
                            />
                            <p v-if="form.errors.new_value" class="mt-1 text-sm text-red-600">{{ form.errors.new_value }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valeur de remplacement</label>
                            <input
                                v-model.number="form.replacement_value"
                                type="number"
                                min="0"
                                step="0.01"
                                :class="[inputClass, form.errors.replacement_value && inputErrorClass]"
                            />
                            <p v-if="form.errors.replacement_value" class="mt-1 text-sm text-red-600">{{ form.errors.replacement_value }}</p>
                        </div>
                    </div>
                </fieldset>

                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Création…' : 'Créer' }}
                    </button>
                    <Link :href="client?.id ? route('clients.show', client.id) : route('vehicles.index')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
