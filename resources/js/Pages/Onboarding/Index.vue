<script setup>
import { useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const props = defineProps({
    employeeCountRanges: Object,
    referralSources: Object,
    industries: Object,
});

const form = useForm({
    name: '',
    employee_count_range: '',
    referral_source: '',
    industry: '',
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const selectClass = 'w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none bg-white';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <OnboardingLayout>
        <h1 class="text-xl font-semibold text-slate-900 mb-1">Créez votre espace</h1>
        <p class="text-sm text-slate-500 mb-6">Quelques informations sur votre organisation</p>

        <form
            @submit.prevent="form.post('/onboarding', { preserveScroll: true })"
            class="space-y-4"
        >
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom de l'organisation</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="Ex: Cabinet Martin & Associés"
                    :class="[inputClass, form.errors.name && inputErrorClass]"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="industry" class="block text-sm font-medium text-slate-700 mb-1">Secteur d'activité</label>
                <select
                    id="industry"
                    v-model="form.industry"
                    required
                    :class="[selectClass, form.errors.industry && inputErrorClass]"
                >
                    <option value="">Sélectionnez un secteur</option>
                    <option
                        v-for="(label, value) in industries"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <p v-if="form.errors.industry" class="mt-1 text-sm text-red-600">{{ form.errors.industry }}</p>
            </div>

            <div>
                <label for="employee_count_range" class="block text-sm font-medium text-slate-700 mb-1">Nombre d'employés</label>
                <select
                    id="employee_count_range"
                    v-model="form.employee_count_range"
                    required
                    :class="[selectClass, form.errors.employee_count_range && inputErrorClass]"
                >
                    <option value="">Sélectionnez une tranche</option>
                    <option
                        v-for="(label, value) in employeeCountRanges"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <p v-if="form.errors.employee_count_range" class="mt-1 text-sm text-red-600">
                    {{ form.errors.employee_count_range }}
                </p>
            </div>

            <div>
                <label for="referral_source" class="block text-sm font-medium text-slate-700 mb-1">Comment nous avez-vous connus ?</label>
                <select
                    id="referral_source"
                    v-model="form.referral_source"
                    required
                    :class="[selectClass, form.errors.referral_source && inputErrorClass]"
                >
                    <option value="">Sélectionnez une option</option>
                    <option
                        v-for="(label, value) in referralSources"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <p v-if="form.errors.referral_source" class="mt-1 text-sm text-red-600">
                    {{ form.errors.referral_source }}
                </p>
            </div>

            <button
                type="submit"
                class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                {{ form.processing ? 'Enregistrement…' : "Compléter l'inscription" }}
            </button>
        </form>
    </OnboardingLayout>
</template>
