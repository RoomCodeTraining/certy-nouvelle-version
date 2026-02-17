<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { route } from '@/route';

const props = defineProps({
    companies: Array,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Bordereaux', href: '/bordereaux' },
    { label: 'Nouveau bordereau' },
];

const form = useForm({
    company_id: '',
    period_start: '',
    period_end: '',
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Nouveau bordereau" />
        </template>

        <div class="min-h-[60vh] flex flex-col w-full max-w-none">
            <p class="text-slate-600 text-sm mb-4">
                Choisissez une compagnie et la période (du → au). Sont inclus les contrats dont la date de création est comprise entre ces deux dates. Le bordereau recevra une référence au format BR- + 11 caractères alphanumériques majuscules.
            </p>

            <form @submit.prevent="form.post(route('bordereaux.store'))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Compagnie *</label>
                    <SearchableSelect
                        v-model="form.company_id"
                        :options="companies"
                        value-key="id"
                        label-key="name"
                        image-key="logo_url"
                        placeholder="— Sélectionner une compagnie —"
                        :required="true"
                        :error="!!form.errors.company_id"
                        :input-class="inputClass"
                        search-placeholder="Rechercher une compagnie…"
                    />
                    <p v-if="form.errors.company_id" class="mt-1 text-sm text-red-600">{{ form.errors.company_id }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Période du *</label>
                        <input
                            v-model="form.period_start"
                            type="date"
                            required
                            :class="[inputClass, form.errors.period_start && inputErrorClass]"
                        />
                        <p v-if="form.errors.period_start" class="mt-1 text-sm text-red-600">{{ form.errors.period_start }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">au *</label>
                        <input
                            v-model="form.period_end"
                            type="date"
                            required
                            :class="[inputClass, form.errors.period_end && inputErrorClass]"
                        />
                        <p v-if="form.errors.period_end" class="mt-1 text-sm text-red-600">{{ form.errors.period_end }}</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Création…' : 'Créer le bordereau' }}
                    </button>
                    <Link :href="route('bordereaux.index')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
