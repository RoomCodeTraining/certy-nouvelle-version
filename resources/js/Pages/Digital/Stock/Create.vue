<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const props = defineProps({
    organizations: { type: Array, default: () => [] },
    certificateTypes: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Stock', href: route('digital.stock') },
    { label: 'Demande de stock' },
];

const form = useForm({
    quantity: 1,
    certificate_type_id: '',
    organization_id: '',
});

// Chaque item = rattachement avec owner (compagnie) { id, name, code } ; n'afficher que les noms de compagnie
const organizationOptions = computed(() => {
    const o = props.organizations;
    if (!Array.isArray(o)) return [];
    return o.map((x) => {
        const owner = x.owner ?? x.organization ?? x.company ?? x;
        const id = owner.id ?? x.id ?? owner.value ?? x.value;
        const name = owner.name ?? owner.label ?? x.name ?? x.label ?? '—';
        const code = owner.code ?? x.code ?? '';
        const label = code ? `${name} (${code})` : name;
        return { value: String(id ?? ''), label: label || '—' };
    }).filter((opt) => opt.value);
});
const certificateTypeOptions = computed(() => {
    const t = props.certificateTypes;
    if (!Array.isArray(t)) return [];
    return t.map((x) => ({
        value: String(x.id ?? x.value ?? ''),
        label: x.name ?? x.label ?? x.code ?? '—',
    }));
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Demande de stock" />
        </template>

        <div class="min-h-[60vh] flex flex-col">
            <p class="text-sm text-slate-600 mb-4">
                Envoyer une demande de stock d'attestations à la plateforme ASACI (service externe).
            </p>

            <div v-if="$page.props.flash?.error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
                {{ $page.props.flash.error }}
            </div>

            <div class="flex-1 w-full max-w-none">
                <form @submit.prevent="form.post(route('digital.transactions.store'))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quantité *</label>
                        <input
                            v-model.number="form.quantity"
                            type="number"
                            min="1"
                            required
                            :class="[inputClass, form.errors.quantity && inputErrorClass]"
                            placeholder="Nombre d'attestations"
                        />
                        <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type d'attestation *</label>
                        <select v-model="form.certificate_type_id" required :class="[inputClass, form.errors.certificate_type_id && inputErrorClass]">
                            <option value="">— Choisir —</option>
                            <option v-for="opt in certificateTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="form.errors.certificate_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.certificate_type_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Compagnie *</label>
                        <select v-model="form.organization_id" required :class="[inputClass, form.errors.organization_id && inputErrorClass]">
                            <option value="">— Choisir une compagnie —</option>
                            <option v-for="opt in organizationOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="form.errors.organization_id" class="mt-1 text-sm text-red-600">{{ form.errors.organization_id }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3 pt-6 border-t border-slate-200">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Envoi…' : 'Envoyer la demande' }}
                        </button>
                        <Link :href="route('digital.stock')" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                            Annuler
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
