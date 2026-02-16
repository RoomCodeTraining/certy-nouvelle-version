<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { route } from '@/route';
import { contractTypeLabel, attestationColorLabel, attestationColorClasses } from '@/utils/contractTypes';

const props = defineProps({
    contract: Object,
    clients: Array,
    companies: Array,
    contractTypes: Array,
});

const contractTitle = `${contractTypeLabel(props.contract?.contract_type)} — ${props.contract?.company?.name ?? 'Contrat'}`.replace(/^ — | — $/, '') || 'Contrat';
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Contrats', href: '/contracts' },
    { label: contractTitle, href: route('contracts.show', props.contract?.id) },
    { label: 'Modifier' },
];

const form = useForm({
    client_id: props.contract.client_id,
    vehicle_id: props.contract.vehicle_id,
    company_id: props.contract.company_id,
    contract_type: props.contract.contract_type,
    status: props.contract.status ?? 'draft',
    start_date: props.contract.start_date ?? '',
    end_date: props.contract.end_date ?? '',
    reduction_amount: props.contract.reduction_amount ?? 0,
    reduction_bns: props.contract.reduction_bns ?? null,
    reduction_on_commission: props.contract.reduction_on_commission ?? null,
    reduction_on_profession_percent: props.contract.reduction_on_profession_percent ?? null,
    reduction_on_profession_amount: props.contract.reduction_on_profession_amount ?? null,
    company_accessory: props.contract.company_accessory ?? 0,
    agency_accessory: props.contract.agency_accessory ?? 0,
    commission_amount: props.contract.commission_amount ?? 0,
});

const selectedClient = computed(() => props.clients.find(c => String(c.id) === String(form.client_id)));
const vehiclesForClient = computed(() => selectedClient.value?.vehicles ?? []);
const vehiclesForSelect = computed(() =>
    vehiclesForClient.value.map(v => ({ ...v, name: v.registration_number || `Sans immat (id ${v.id})` }))
);

function onClientChange() {
    if (!vehiclesForClient.value.some(v => String(v.id) === String(form.vehicle_id))) {
        form.vehicle_id = '';
    }
}

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modifier le contrat" />
        </template>

        <div class="min-h-[60vh] flex flex-col">
            <div class="flex-1 w-full max-w-none">
                <form @submit.prevent="form.put(route('contracts.update', contract.id))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Client *</label>
                <SearchableSelect
                    v-model="form.client_id"
                    :options="clients"
                    value-key="id"
                    label-key="full_name"
                    placeholder="—"
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
                    placeholder="—"
                    :required="true"
                    :error="!!form.errors.vehicle_id"
                    :input-class="inputClass"
                    :disabled="!form.client_id"
                    search-placeholder="Rechercher…"
                />
                <p v-if="form.errors.vehicle_id" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_id }}</p>
                <p v-if="form.client_id && !vehiclesForClient.length" class="mt-1 text-sm text-amber-600">Ce client n’a aucun véhicule.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Compagnie *</label>
                <SearchableSelect
                    v-model="form.company_id"
                    :options="companies"
                    value-key="id"
                    label-key="name"
                    placeholder="—"
                    :required="true"
                    :error="!!form.errors.company_id"
                    :input-class="inputClass"
                    search-placeholder="Rechercher une compagnie…"
                />
                <p v-if="form.errors.company_id" class="mt-1 text-sm text-red-600">{{ form.errors.company_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Type de contrat *</label>
                <SearchableSelect
                    v-model="form.contract_type"
                    :options="contractTypes"
                    value-key="value"
                    label-key="label"
                    :required="true"
                    :input-class="inputClass"
                    search-placeholder="Rechercher…"
                />
                <p class="mt-2 flex items-center gap-2">
                    <span class="text-xs text-slate-500">Attestation :</span>
                    <span
                        :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', attestationColorClasses(form.contract_type)]"
                    >
                        {{ attestationColorLabel(form.contract_type) }}
                    </span>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                <select v-model="form.status" :class="inputClass">
                    <option value="draft">Brouillon</option>
                    <option value="validated">Validé</option>
                    <option value="active">Actif</option>
                    <option value="cancelled">Annulé</option>
                    <option value="expired">Expiré</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date de début</label>
                    <input v-model="form.start_date" type="date" :class="[inputClass, form.errors.start_date && inputErrorClass]" />
                    <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date de fin</label>
                    <input v-model="form.end_date" type="date" :class="[inputClass, form.errors.end_date && inputErrorClass]" />
                    <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Accessoire compagnie (FCFA)</label>
                    <input v-model.number="form.company_accessory" type="number" min="0" step="1" :class="[inputClass, form.errors.company_accessory && inputErrorClass]" />
                    <p v-if="form.errors.company_accessory" class="mt-1 text-sm text-red-600">{{ form.errors.company_accessory }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Accessoire agence (FCFA)</label>
                    <input v-model.number="form.agency_accessory" type="number" min="0" step="1" :class="[inputClass, form.errors.agency_accessory && inputErrorClass]" />
                    <p v-if="form.errors.agency_accessory" class="mt-1 text-sm text-red-600">{{ form.errors.agency_accessory }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Commission (FCFA)</label>
                    <input v-model.number="form.commission_amount" type="number" min="0" step="1" :class="[inputClass, form.errors.commission_amount && inputErrorClass]" />
                    <p v-if="form.errors.commission_amount" class="mt-1 text-sm text-red-600">{{ form.errors.commission_amount }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction BNS</label>
                    <input v-model.number="form.reduction_bns" type="number" min="0" max="100" step="0.01" :class="[inputClass, form.errors.reduction_bns && inputErrorClass]" placeholder="0–100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction sur commission</label>
                    <input v-model.number="form.reduction_on_commission" type="number" min="0" max="100" step="0.01" :class="[inputClass, form.errors.reduction_on_commission && inputErrorClass]" placeholder="0–100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">(%) Réduction profession</label>
                    <input v-model.number="form.reduction_on_profession_percent" type="number" min="0" max="100" step="0.01" :class="[inputClass, form.errors.reduction_on_profession_percent && inputErrorClass]" placeholder="0–100" />
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <Link :href="route('contracts.show', contract.id)" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                    Annuler
                </Link>
            </div>
        </form>
            </div>
        </div>
    </DashboardLayout>
</template>
