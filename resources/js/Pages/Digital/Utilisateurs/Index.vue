<script setup>
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import { route } from '@/route';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    utilisateurs: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
});

const list = Array.isArray(props.utilisateurs)
    ? props.utilisateurs
    : (Array.isArray(props.utilisateurs?.data) ? props.utilisateurs.data : []);
const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Digital', href: '/digital/attestations' },
    { label: 'Utilisateurs' },
];

// Rôles office_user : standard_user = Producteur, office_admin = Administrateur Representation, etc.
function getRoleDisplayLabel(role) {
    if (!role) return '';
    const raw = String(role.label ?? role.name ?? role.code ?? '').toLowerCase().replace(/\s+/g, '_');
    const map = { standard_user: 'Producteur', office_admin: 'Administrateur Representation', administrateur: 'Administrateur', representation: 'Representation' };
    return map[raw] ?? role.label ?? role.name ?? role.code ?? '';
}
// Utilisateur : nom, username, rôle sur 3 lignes | Bureau actuel : nom, code (+ "Bureau principal" si master)
const columns = [
    {
        key: 'utilisateur',
        label: 'Utilisateur',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const name = row.name ?? ([row.first_name, row.last_name].filter(Boolean).join(' ') || row.username || '—');
            const username = row.username ?? '';
            const roleLabel = typeof row.role === 'object' && row.role
                ? getRoleDisplayLabel(row.role)
                : (typeof row.role === 'string' ? getRoleDisplayLabel({ name: row.role }) : '');
            const lines = [name, username, roleLabel].filter(Boolean);
            return lines.length ? lines.join('\n') : '—';
        },
    },
    { key: 'email', label: 'Email', getValue: (row) => row.email ?? '—' },
    {
        key: 'bureau_actuel',
        label: 'Bureau actuel',
        cellClass: 'whitespace-pre-line align-top',
        getValue: (row) => {
            const office = row.current_office;
            if (typeof office !== 'object' || !office) return row.bureau_actuel ?? row.office_name ?? '—';
            const name = office.name ?? '';
            const code = office.code ?? '';
            const master = office.is_master_office ? 'Bureau principal' : '';
            const lines = [name, code, master].filter(Boolean);
            return lines.length ? lines.join('\n') : '—';
        },
    },
    {
        key: 'statut',
        label: 'Statut',
        type: 'badge',
        getValue: (row) => row.is_disabled ? 'Désactivé' : (row.status ?? row.statut ?? 'Actif'),
        getBadgeClass: (row) => row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800',
    },
    {
        key: 'created_at',
        label: 'Date de création',
        getValue: (row) => row.formatted_created_at ?? formatDate(row.created_at),
    },
];

const { confirm: confirmDialog } = useConfirm();
const id = (row) => row.id ?? row.email;

function disableUser(row) {
    confirmDialog({
        title: 'Désactiver l\'utilisateur',
        message: 'Voulez-vous désactiver cet utilisateur ?',
        confirmLabel: 'Désactiver',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.post(route('digital.utilisateurs.disable', id(row)));
    });
}
function enableUser(row) {
    confirmDialog({
        title: 'Activer l\'utilisateur',
        message: 'Voulez-vous activer cet utilisateur ?',
        confirmLabel: 'Activer',
        variant: 'default',
    }).then((ok) => {
        if (ok) router.post(route('digital.utilisateurs.enable', id(row)));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Utilisateurs">
                <template #actions>
                    <Link
                        :href="route('digital.utilisateurs.create')"
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 gap-2 px-4 py-3 sm:py-2 rounded-xl sm:rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                    >
                        Créer un utilisateur
                    </Link>
                </template>
            </PageHeader>
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Données issues de la plateforme ASACI. Liste des utilisateurs du service externe.
        </p>

        <div v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6">
            {{ error }}
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-900">Liste des utilisateurs</h2>
                <Link href="/digital/utilisateurs" class="text-sm text-slate-600 hover:text-slate-900 font-medium">Actualiser</Link>
            </div>
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div v-for="row in list" :key="id(row)" class="p-4">
                    <p class="font-medium text-slate-900">{{ row.name ?? ([row.first_name, row.last_name].filter(Boolean).join(' ') || row.username || '—') }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ row.username ?? '' }}</p>
                    <p class="text-sm text-slate-700 mt-1">{{ row.email ?? '—' }}</p>
                    <span
                        :class="['inline-flex mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium', row.is_disabled ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']"
                    >
                        {{ row.is_disabled ? 'Désactivé' : 'Actif' }}
                    </span>
                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-slate-100">
                        <DataTableAction label="Modifier" icon="edit" :to="route('digital.utilisateurs.edit', id(row))" />
                        <template v-if="row.is_disabled">
                            <DataTableAction label="Activer" icon="eye" @click="enableUser(row)" />
                        </template>
                        <template v-else>
                            <DataTableAction label="Désactiver" icon="x" variant="danger" @click="disableUser(row)" />
                        </template>
                    </div>
                </div>
                <div v-if="!list.length" class="py-10 px-4 text-center text-slate-500 text-sm">Aucun utilisateur.</div>
            </div>
            <!-- Desktop : tableau -->
            <div class="hidden md:block">
                <DataTable
                    :data="list"
                    :columns="columns"
                    :row-key="(row) => row.id ?? row.email"
                    empty-message="Aucun utilisateur."
                >
                    <template #actions="{ row }">
                        <div class="flex items-center gap-1">
                            <DataTableAction label="Modifier" icon="edit" :to="route('digital.utilisateurs.edit', id(row))" />
                            <template v-if="row.is_disabled">
                                <DataTableAction label="Activer" icon="eye" @click="enableUser(row)" />
                            </template>
                            <template v-else>
                                <DataTableAction label="Désactiver" icon="x" variant="danger" @click="disableUser(row)" />
                            </template>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </DashboardLayout>
</template>
