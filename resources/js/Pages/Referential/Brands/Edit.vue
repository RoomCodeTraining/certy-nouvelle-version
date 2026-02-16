<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ReferentialTabs from '@/Components/ReferentialTabs.vue';
import { route } from '@/route';

const props = defineProps({ brand: Object });

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Référentiel', href: route('referential.brands.index') },
    { label: 'Marques', href: route('referential.brands.index') },
    { label: 'Modifier' },
];

const form = useForm({
    name: props.brand?.name ?? '',
    slug: props.brand?.slug ?? '',
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modifier la marque" />
        </template>

        <ReferentialTabs active="brands" />

        <div class="min-h-[60vh] flex flex-col w-full max-w-none">
            <form @submit.prevent="form.put(route('referential.brands.update', brand.id))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                    <input v-model="form.name" type="text" required :class="[inputClass, form.errors.name && inputErrorClass]" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
                    <input v-model="form.slug" type="text" :class="[inputClass, form.errors.slug && inputErrorClass]" placeholder="Optionnel" />
                    <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                </div>
                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link :href="route('referential.brands.index')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
