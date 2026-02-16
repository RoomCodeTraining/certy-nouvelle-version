<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const props = defineProps({
    bureau: Object,
    officeTypes: { type: Array, default: () => [] },
});

const b = props.bureau ?? {};
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Bureaux', href: '/digital/bureaux' },
    { label: 'Modifier' },
];

const form = useForm({
    name: b.name ?? b.office_name ?? '',
    code: b.code ?? '',
    address: b.address ?? b.adresse ?? '',
    email: b.email ?? '',
    telephone: b.telephone ?? b.phone ?? '',
    office_type_id: (typeof b.office_type === 'object' && b.office_type?.id) ? String(b.office_type.id) : (b.office_type_id ?? ''),
});

const typeOptions = computed(() => {
    const t = props.officeTypes;
    if (!Array.isArray(t)) return [];
    return t.map((x) => ({ value: String(x.id ?? x.value ?? ''), label: x.name ?? x.label ?? x.code ?? '—' }));
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modifier le bureau" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Modifier les informations du bureau sur la plateforme ASACI.
        </p>

        <div v-if="$page.props.flash?.error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ $page.props.flash.error }}
        </div>

        <div class="min-h-[60vh] flex flex-col">
            <div class="flex-1 w-full max-w-none">
                <form @submit.prevent="form.put(route('digital.bureaux.update', b.id))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                    <input v-model="form.name" type="text" required :class="[inputClass, form.errors.name && inputErrorClass]" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                    <input v-model="form.code" type="text" :class="[inputClass, form.errors.code && inputErrorClass]" />
                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Adresse</label>
                    <input v-model="form.address" type="text" :class="[inputClass, form.errors.address && inputErrorClass]" />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" :class="[inputClass, form.errors.email && inputErrorClass]" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                        <input v-model="form.telephone" type="text" :class="[inputClass, form.errors.telephone && inputErrorClass]" />
                        <p v-if="form.errors.telephone" class="mt-1 text-sm text-red-600">{{ form.errors.telephone }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Type de bureau</label>
                    <select v-model="form.office_type_id" :class="inputClass">
                        <option value="">—</option>
                        <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link :href="route('digital.bureaux')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
            </div>
        </div>
    </DashboardLayout>
</template>
