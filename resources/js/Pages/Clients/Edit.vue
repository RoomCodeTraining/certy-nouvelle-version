<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const TYPE_TAPP = 'TAPP';
const TYPE_TAPM = 'TAPM';

const props = defineProps({
    client: Object,
    typeAssureOptions: Array,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Clients', href: '/clients' },
    { label: props.client?.full_name ?? 'Client', href: route('clients.show', props.client?.id) },
    { label: 'Modifier' },
];

const form = useForm({
    type_assure: props.client.type_assure ?? TYPE_TAPP,
    full_name: props.client.full_name,
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    address: props.client.address ?? '',
    postal_address: props.client.postal_address ?? '',
    profession: props.client.profession?.name ?? '',
});

const nameLabel = computed(() =>
    form.type_assure === TYPE_TAPP ? 'Nom complet *' : 'Raison sociale *'
);
const namePlaceholder = computed(() =>
    form.type_assure === TYPE_TAPP ? 'Nom et prénom du client' : 'Raison sociale de l\'entreprise'
);
const professionLabel = computed(() =>
    form.type_assure === TYPE_TAPP ? 'Profession *' : 'Activité / Secteur *'
);
const professionPlaceholder = computed(() =>
    form.type_assure === TYPE_TAPP ? 'Profession' : 'Ex. Commerce, BTP, Services…'
);

const inputClass = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modifier le client" />
        </template>

        <div class="min-h-[60vh] flex flex-col w-full max-w-none">
            <form @submit.prevent="form.put(route('clients.update', client.id))" class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full">
                <!-- Type assuré : TAPP / TAPM -->
                <div class="pb-4 border-b border-slate-200">
                    <span class="block text-sm font-medium text-slate-700 mb-3">Type d'assuré *</span>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.type_assure"
                                type="radio"
                                :value="TYPE_TAPP"
                                class="rounded-full border-slate-300 text-slate-900 focus:ring-slate-400"
                            />
                            <span class="text-sm text-slate-700">Personne physique (TAPP)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.type_assure"
                                type="radio"
                                :value="TYPE_TAPM"
                                class="rounded-full border-slate-300 text-slate-900 focus:ring-slate-400"
                            />
                            <span class="text-sm text-slate-700">Personne morale (TAPM)</span>
                        </label>
                    </div>
                    <p v-if="form.errors.type_assure" class="mt-1 text-sm text-red-600">{{ form.errors.type_assure }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ nameLabel }}</label>
                    <input v-model="form.full_name" type="text" required :class="[inputClass, form.errors.full_name && inputErrorClass]" :placeholder="namePlaceholder" />
                    <p v-if="form.errors.full_name" class="mt-1 text-sm text-red-600">{{ form.errors.full_name }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" :class="[inputClass, form.errors.email && inputErrorClass]" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                        <input v-model="form.phone" type="text" :class="[inputClass, form.errors.phone && inputErrorClass]" />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Adresse</label>
                    <input v-model="form.address" type="text" :class="[inputClass, form.errors.address && inputErrorClass]" placeholder="Adresse" />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Code postal</label>
                    <input v-model="form.postal_address" type="text" :class="[inputClass, form.errors.postal_address && inputErrorClass]" placeholder="Code postal" />
                    <p v-if="form.errors.postal_address" class="mt-1 text-sm text-red-600">{{ form.errors.postal_address }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ professionLabel }}</label>
                    <input v-model="form.profession" type="text" required :class="[inputClass, form.errors.profession && inputErrorClass]" :placeholder="professionPlaceholder" />
                    <p v-if="form.errors.profession" class="mt-1 text-sm text-red-600">{{ form.errors.profession }}</p>
                </div>
                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link :href="route('clients.show', client.id)" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
