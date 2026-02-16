<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    bordereau: Object,
    contracts: Array,
    can_validate: Boolean,
    can_delete: Boolean,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

/** Référence bordereau : BR- + 11 caractères alphanumériques majuscules. */
const bordereauRef = computed(
    () => props.bordereau?.reference ?? '—',
);

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Bordereaux', href: '/bordereaux' },
    { label: bordereauRef },
];

const statusLabels = {
    draft: 'Brouillon',
    validated: 'Validé',
    submitted: 'Soumis',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    paid: 'Payé',
};

const { confirm: confirmDialog } = useConfirm();
function confirmDelete() {
    confirmDialog({
        title: 'Supprimer le bordereau',
        message: `Voulez-vous vraiment supprimer le bordereau « ${props.bordereau?.reference} » ?`,
        confirmLabel: 'Supprimer',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.delete(route('bordereaux.destroy', props.bordereau?.id));
    });
}

function formatXOF(value) {
    if (value == null) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' F CFA';
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="bordereauRef">
                <template #actions>
                    <button
                        v-if="can_validate"
                        type="button"
                        @click="router.post(route('bordereaux.validate', bordereau.id))"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700"
                    >
                        Valider
                    </button>
                    <button
                        v-if="can_delete"
                        type="button"
                        @click="confirmDelete"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-red-200 text-red-700 text-sm font-medium rounded-lg hover:bg-red-50"
                    >
                        Supprimer
                    </button>
                    <a
                        :href="route('bordereaux.pdf', bordereau.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                    >
                        Export PDF
                    </a>
                    <a
                        :href="route('bordereaux.excel', bordereau.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                    >
                        Export Excel
                    </a>
                </template>
            </PageHeader>
        </template>

        <div class="min-h-[60vh] flex flex-col">
            <p v-if="flashSuccess" class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                {{ flashSuccess }}
            </p>
            <p v-if="flashError" class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                {{ flashError }}
            </p>

            <div class="rounded-xl border border-slate-200 bg-white p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Période et compagnie</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-slate-500">Référence</dt>
                        <dd class="text-slate-900 font-mono font-semibold">{{ bordereauRef }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Compagnie</dt>
                        <dd class="text-slate-900 font-medium">{{ bordereau.company?.name ?? '—' }}{{ bordereau.company?.code ? ` (${bordereau.company.code})` : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Période</dt>
                        <dd class="text-slate-900">{{ formatDate(bordereau.period_start) }} → {{ formatDate(bordereau.period_end) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Montant total</dt>
                        <dd class="text-slate-900 font-semibold">{{ formatXOF(bordereau.total_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Commission courtier</dt>
                        <dd class="text-slate-900 font-semibold">
                            {{ formatXOF(bordereau.total_commission) }}<template v-if="bordereau.commission_pct != null"> ({{ Number(bordereau.commission_pct).toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }} %)</template>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Statut</dt>
                        <dd>
                            <span
                                :class="[
                                    'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    bordereau.status === 'validated' || bordereau.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : bordereau.status === 'approved' ? 'bg-sky-100 text-sky-800' : bordereau.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800',
                                ]"
                            >
                                {{ statusLabels[bordereau.status] ?? bordereau.status ?? '—' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden mb-6">
                <div class="p-4 md:p-6 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Contrats de la période</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Contrats dont la date de début est comprise entre {{ formatDate(bordereau.period_start) }} et {{ formatDate(bordereau.period_end) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Date début</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Date fin</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Véhicule</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">N° attestation</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr
                                v-for="c in contracts"
                                :key="c.id"
                                class="hover:bg-slate-50/50"
                            >
                                <td class="px-4 py-3 text-sm text-slate-700">{{ formatDate(c.start_date) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ formatDate(c.end_date) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ c.client }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ c.vehicle }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ formatXOF(c.total_amount) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 font-mono">{{ c.attestation_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('contracts.show', c.id)" class="text-sky-600 hover:underline text-sm font-medium">Voir</Link>
                                </td>
                            </tr>
                            <tr v-if="!contracts.length">
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    Aucun contrat sur cette période pour cette compagnie.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Link
                :href="route('bordereaux.index')"
                class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
            >
                Retour à la liste des bordereaux
            </Link>
        </div>
    </DashboardLayout>
</template>
