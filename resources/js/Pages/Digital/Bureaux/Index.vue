<script setup>
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import { route } from '@/route';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    bureaux: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
});

const list = Array.isArray(props.bureaux)
    ? props.bureaux
    : (Array.isArray(props.bureaux?.data) ? props.bureaux.data : []);
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Bureaux' },
];

// Bureau et Contact sur deux lignes (nom + code, email + tél)
const columns = [
    {
        key: 'bureau',
        label: 'Bureau',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const name = row.name ?? row.office_name ?? row.label ?? '—';
            const code = row.code ?? '';
            return code ? `${name}\n${code}` : name;
        },
    },
    {
        key: 'adresse',
        label: 'Adresse',
        getValue: (row) => row.address ?? row.adresse ?? row.adress ?? '—',
    },
    {
        key: 'contact',
        label: 'Contact',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const email = row.email ?? '';
            const tel = row.telephone ?? row.phone ?? row.contact_phone ?? '';
            const parts = [email, tel].filter(Boolean);
            return parts.length ? parts.join('\n') : '—';
        },
    },
    {
        key: 'organisation',
        label: 'Organisation',
        getValue: (row) => (typeof row.organization === 'object' && row.organization?.name) ? row.organization.name : (row.organisation?.name ?? row.organization ?? row.organisation ?? row.organization_name ?? '—'),
    },
    {
        key: 'statut',
        label: 'Statut',
        type: 'badge',
        getValue: (row) => row.is_disabled ? 'Désactivé' : (row.status ?? row.statut ?? row.state ?? 'Actif'),
        getBadgeClass: (row) => row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800',
    },
];

const { confirm: confirmDialog } = useConfirm();
const id = (row) => row.id ?? row.reference;

function disableBureau(row) {
    confirmDialog({
        title: 'Désactiver le bureau',
        message: 'Voulez-vous désactiver ce bureau ?',
        confirmLabel: 'Désactiver',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.post(route('digital.bureaux.disable', id(row)));
    });
}
function enableBureau(row) {
    confirmDialog({
        title: 'Activer le bureau',
        message: 'Voulez-vous activer ce bureau ?',
        confirmLabel: 'Activer',
        variant: 'default',
    }).then((ok) => {
        if (ok) router.post(route('digital.bureaux.enable', id(row)));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Bureaux">
                <template #actions>
                    <Link
                        :href="route('digital.bureaux.create')"
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 gap-2 px-4 py-3 sm:py-2 rounded-xl sm:rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                    >
                        Créer un bureau
                    </Link>
                </template>
            </PageHeader>
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Données issues de la plateforme ASACI. Liste des bureaux.
        </p>

        <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ error }}
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-900">Liste des bureaux</h2>
                <Link href="/digital/bureaux" class="text-sm text-slate-600 hover:text-slate-900 font-medium">Actualiser</Link>
            </div>
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div v-for="row in list" :key="id(row)" class="p-4">
                    <p class="font-medium text-slate-900">{{ row.name ?? row.office_name ?? row.label ?? '—' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ row.code ?? '' }}</p>
                    <p class="text-sm text-slate-700 mt-1">{{ row.address ?? row.adresse ?? '—' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ row.email ?? '' }} {{ row.telephone ?? row.phone ?? '' }}</p>
                    <span
                        :class="['inline-flex mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium', row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']"
                    >
                        {{ row.is_disabled ? 'Désactivé' : 'Actif' }}
                    </span>
                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-slate-100">
                        <DataTableAction label="Modifier" icon="edit" :to="route('digital.bureaux.edit', id(row))" />
                        <template v-if="row.is_disabled">
                            <DataTableAction label="Activer" icon="eye" @click="enableBureau(row)" />
                        </template>
                        <template v-else>
                            <DataTableAction label="Désactiver" icon="x" variant="danger" @click="disableBureau(row)" />
                        </template>
                    </div>
                </div>
                <div v-if="!list.length" class="py-10 px-4 text-center text-slate-500 text-sm">Aucun bureau.</div>
            </div>
            <!-- Desktop : tableau -->
            <div class="hidden md:block">
                <DataTable
                    :data="list"
                    :columns="columns"
                    :row-key="(row) => row.id ?? row.reference"
                    empty-message="Aucun bureau."
                >
                    <template #actions="{ row }">
                        <div class="flex items-center gap-1">
                            <DataTableAction label="Modifier" icon="edit" :to="route('digital.bureaux.edit', id(row))" />
                            <template v-if="row.is_disabled">
                                <DataTableAction label="Activer" icon="eye" @click="enableBureau(row)" />
                            </template>
                            <template v-else>
                                <DataTableAction label="Désactiver" icon="x" variant="danger" @click="disableBureau(row)" />
                            </template>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </DashboardLayout>
</template>
