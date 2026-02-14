<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    organization: Object,
    industries: Object,
    employeeCountRanges: Object,
    referralSources: Object,
});

const form = useForm({
    name: props.organization?.name ?? '',
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';

</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Mon organisation</h1>
        </template>

        <div class="max-w-2xl">
            <p class="text-slate-600 text-sm mb-6">Informations de votre organisation</p>

            <form @submit.prevent="form.put('/settings/organization')" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :class="[inputClass, form.errors.name && inputErrorClass]"
                        placeholder="Nom de l'organisation"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div v-if="organization" class="pt-2 space-y-2">
                    <p class="text-xs text-slate-500">Informations en lecture seule</p>
                    <div class="rounded-lg border border-slate-100 p-3 space-y-2 text-sm">
                        <p><span class="text-slate-500">Slug</span> · {{ organization.slug }}</p>
                        <p v-if="organization.industry"><span class="text-slate-500">Secteur</span> · {{ industries?.[organization.industry] ?? organization.industry }}</p>
                        <p v-if="organization.employee_count_range"><span class="text-slate-500">Employés</span> · {{ employeeCountRanges?.[organization.employee_count_range] ?? organization.employee_count_range }}</p>
                        <p v-if="organization.referral_source"><span class="text-slate-500">Découverte</span> · {{ referralSources?.[organization.referral_source] ?? organization.referral_source }}</p>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link
                        href="/dashboard"
                        class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
                    >
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
