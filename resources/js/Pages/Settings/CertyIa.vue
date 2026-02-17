<script setup>
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const props = defineProps({
    certyIaName: String,
    certyIaEnabledAtApp: Boolean,
    certyIaEnabled: Boolean,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Paramètres', href: '/settings/profile' },
    { label: props.certyIaName },
];

const form = useForm({
    certy_ia_enabled: props.certyIaEnabled,
});
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="certyIaName" />
        </template>

        <div class="max-w-xl">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <p class="text-sm text-slate-600 mb-4">
                    Activez l'assistant pour permettre aux utilisateurs de poser des questions sur les clients, véhicules, contrats et bordereaux (données accessibles selon leurs droits).
                </p>
                <form @submit.prevent="form.put(route('settings.certy-ia.update'))" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label for="certy_ia_enabled" class="text-sm font-medium text-slate-700">
                            Activer {{ certyIaName }}
                        </label>
                        <input
                            id="certy_ia_enabled"
                            v-model="form.certy_ia_enabled"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                            :disabled="!certyIaEnabledAtApp"
                        />
                    </div>
                    <p v-if="!certyIaEnabledAtApp" class="text-xs text-slate-500">
                        L'assistant est désactivé au niveau de l'application. Contactez l'administrateur.
                    </p>
                    <div class="flex gap-3 pt-2">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
