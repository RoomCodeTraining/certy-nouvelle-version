<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const props = defineProps({
    utilisateur: Object,
    roles: { type: Array, default: () => [] },
    offices: { type: Array, default: () => [] },
});

const u = props.utilisateur ?? {};
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Utilisateurs', href: '/digital/utilisateurs' },
    { label: 'Modifier' },
];

const form = useForm({
    name: u.name ?? u.full_name ?? '',
    email: u.email ?? '',
    username: u.username ?? '',
    role_id: (typeof u.role === 'object' && u.role?.id) ? String(u.role.id) : (u.role_id ?? ''),
    office_id: (typeof u.current_office === 'object' && u.current_office?.id) ? String(u.current_office.id) : (u.office_id ?? ''),
});

// Rôles office_user : standard_user = Producteur, office_admin = Administrateur Representation, etc.
const ROLE_LABEL_MAP = {
    standard_user: 'Producteur',
    office_admin: 'Administrateur Representation',
    administrateur: 'Administrateur',
    representation: 'Representation',
};
function getRoleLabel(role) {
    const raw = (role?.name ?? role?.label ?? role?.code ?? '').toString().toLowerCase().replace(/\s+/g, '_');
    return ROLE_LABEL_MAP[raw] ?? role?.name ?? role?.label ?? role?.code ?? '—';
}
const roleOptions = computed(() => {
    const r = props.roles;
    if (!Array.isArray(r)) return [];
    return r.map((x) => ({ value: String(x.id ?? x.value ?? x.code ?? ''), label: getRoleLabel(x) }));
});
const officeOptions = computed(() => {
    const o = props.offices;
    if (!Array.isArray(o)) return [];
    return o.map((x) => ({ value: String(x.id ?? x.value ?? ''), label: (x.name ?? x.label ?? '') + (x.code ? ` (${x.code})` : '') }));
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modifier l'utilisateur" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Modifier les informations de l'utilisateur sur la plateforme ASACI.
        </p>

        <div v-if="$page.props.flash?.error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ $page.props.flash.error }}
        </div>

        <div class="min-h-[60vh] flex flex-col">
            <div class="flex-1 w-full max-w-none">
                <form @submit.prevent="form.put(route('digital.utilisateurs.update', u.id))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                    <input v-model="form.name" type="text" required :class="[inputClass, form.errors.name && inputErrorClass]" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                        <input v-model="form.email" type="email" required :class="[inputClass, form.errors.email && inputErrorClass]" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                        <input v-model="form.username" type="text" :class="[inputClass, form.errors.username && inputErrorClass]" />
                        <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Rôle</label>
                        <select v-model="form.role_id" :class="inputClass">
                            <option value="">—</option>
                            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Bureau</label>
                        <select v-model="form.office_id" :class="inputClass">
                            <option value="">—</option>
                            <option v-for="opt in officeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link :href="route('digital.utilisateurs')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
            </div>
        </div>
    </DashboardLayout>
</template>
