<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';
import { contractTypeLabel } from '@/utils/contractTypes';

const props = defineProps({
    client: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Clients', href: '/clients' },
    { label: props.client?.full_name ?? 'Fiche client' },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="client?.full_name ?? 'Fiche client'">
                <template #actions>
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                    >
                        Modifier
                    </Link>
                    <Link
                        :href="route('vehicles.create.for_client', client.id)"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Ajouter un véhicule
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-sm font-medium text-slate-500 mb-3">Coordonnées</h3>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900">{{ client.email || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd class="font-medium text-slate-900">{{ client.phone || '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Adresse</dt><dd class="font-medium text-slate-900">{{ client.address || '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Adresse postale</dt><dd class="font-medium text-slate-900">{{ client.postal_address || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Profession</dt><dd class="font-medium text-slate-900">{{ client.profession?.name || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Type assuré</dt><dd class="font-medium text-slate-900">{{ client.type_assure || '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-medium text-slate-900">Véhicules</h3>
                    <Link :href="route('vehicles.create.for_client', client.id)" class="text-sm font-medium text-slate-600 hover:text-slate-900">+ Ajouter</Link>
                </div>
                <ul class="divide-y divide-slate-200">
                    <li v-for="v in client.vehicles" :key="v.id" class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <Link :href="route('vehicles.show', v.id)" class="font-medium text-slate-900 hover:underline">
                                {{ v.brand?.name }} {{ v.model?.name }} — {{ v.registration_number || 'Sans immat' }}
                            </Link>
                        </div>
                        <Link :href="route('vehicles.edit', v.id)" class="text-sm text-slate-600 hover:text-slate-900">Modifier</Link>
                    </li>
                </ul>
                <div v-if="!client.vehicles?.length" class="px-6 py-8 text-center text-slate-500 text-sm">
                    Aucun véhicule. <Link :href="route('vehicles.create.for_client', client.id)" class="text-slate-900 underline">Ajouter un véhicule</Link>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-sm font-medium text-slate-900">Contrats</h3>
                </div>
                <ul class="divide-y divide-slate-200">
                    <li v-for="c in client.contracts" :key="c.id" class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <Link :href="route('contracts.show', c.id)" class="font-medium text-slate-900 hover:underline">
                                {{ contractTypeLabel(c.contract_type) }} — {{ c.company?.name }}
                            </Link>
                            <span class="ml-2 text-xs text-slate-500">{{ c.status }}</span>
                        </div>
                        <Link :href="route('contracts.edit', c.id)" class="text-sm text-slate-600 hover:text-slate-900">Modifier</Link>
                    </li>
                </ul>
                <div v-if="!client.contracts?.length" class="px-6 py-8 text-center text-slate-500 text-sm">
                    Aucun contrat.
                </div>
            </div>

            <Link :href="route('clients.index')" class="inline-block text-sm text-slate-600 hover:text-slate-900">← Retour à la liste</Link>
        </div>
    </DashboardLayout>
</template>
