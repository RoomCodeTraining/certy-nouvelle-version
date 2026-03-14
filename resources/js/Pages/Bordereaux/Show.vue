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
                        <dt class="text-sm text-slate-500">Prime TTC</dt>
                        <dd class="text-slate-900 font-semibold">{{ formatXOF(bordereau.total_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Commission courtier</dt>
                        <dd class="text-slate-900 font-semibold">
                            {{ formatXOF(bordereau.total_commission) }}<template v-if="bordereau.commission_pct != null"> ({{ Number(bordereau.commission_pct).toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }} % sur prime nette)</template>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Prime à reverser / Prime effectivement encaissée</dt>
                        <dd class="text-slate-900 font-semibold">{{ formatXOF(bordereau.prime_a_reverser) }}</dd>
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
                    <p class="text-sm text-slate-500 mt-0.5">Contrats créés entre le {{ formatDate(bordereau.period_start) }} et le {{ formatDate(bordereau.period_end) }}. Défilement horizontal pour afficher toutes les colonnes.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-max divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">N°</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">N° attestation</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Police/Assuré</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Nom assuré</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Adresse</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Tél</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Email</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Date effet</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Date échéance</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">N° carte grise</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Marque</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Modèle</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Type</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Énergie</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Immat</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Pl</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Prime nette</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Access.</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Taxe</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Prime TTC</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Taux %</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Commission</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Primes à reverser</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Montant encaissé</th>
                                <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Statut</th>
                                <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr
                                v-for="c in contracts"
                                :key="c.id"
                                class="hover:bg-slate-50/50"
                            >
                                <td class="px-2 py-2 text-slate-700">{{ c.no }}</td>
                                <td class="px-2 py-2 text-slate-700 font-mono">{{ c.attestation_number ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700 font-mono">{{ c.policy_insured ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-900">{{ c.nom_assure ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-600 max-w-[120px] truncate" :title="c.adresse_assure">{{ c.adresse_assure ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.telephone_assure ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700 max-w-[140px] truncate" :title="c.email_assure">{{ c.email_assure ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ formatDate(c.date_effet) }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ formatDate(c.date_echeance) }}</td>
                                <td class="px-2 py-2 text-slate-700 font-mono">{{ c.carte_grise ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.marque ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.modele ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.type_vehicule ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.energie ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700 font-mono">{{ c.immat ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-700">{{ c.nbre_pl ?? '—' }}</td>
                                <td class="px-2 py-2 text-slate-900 text-right tabular-nums">{{ formatXOF(c.prime_nette) }}</td>
                                <td class="px-2 py-2 text-slate-700 text-right tabular-nums">{{ formatXOF(c.accessoire) }}</td>
                                <td class="px-2 py-2 text-slate-700 text-right tabular-nums">{{ formatXOF(c.taxe) }}</td>
                                <td class="px-2 py-2 text-slate-900 text-right tabular-nums font-medium">{{ formatXOF(c.prime_ttc) }}</td>
                                <td class="px-2 py-2 text-slate-700 text-right">{{ c.taux_comm != null ? Number(c.taux_comm).toLocaleString('fr-FR', { minimumFractionDigits: 1 }) + ' %' : '—' }}</td>
                                <td class="px-2 py-2 text-slate-900 text-right tabular-nums">{{ formatXOF(c.commission) }}</td>
                                <td class="px-2 py-2 text-slate-900 text-right tabular-nums font-medium">{{ formatXOF(c.prime_a_reverser) }}</td>
                                <td class="px-2 py-2 text-slate-900 text-right tabular-nums font-medium">{{ formatXOF(c.montant_encaisse) }}</td>
                                <td class="px-2 py-2">
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 rounded text-xs font-medium',
                                            c.status === 'active' ? 'bg-emerald-100 text-emerald-800' : c.status === 'validated' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700',
                                        ]"
                                    >
                                        {{ c.status_label ?? c.status ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <Link :href="route('contracts.show', c.id)" class="text-sky-600 hover:underline font-medium">Voir</Link>
                                </td>
                            </tr>
                            <tr v-if="!contracts.length">
                                <td colspan="26" class="px-4 py-8 text-center text-slate-500">
                                    Aucun contrat sur cette période pour cette compagnie.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-wrap justify-end gap-x-8 gap-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-600">Prime TTC :</span>
                        <span class="font-semibold text-slate-900 tabular-nums">{{ formatXOF(bordereau.total_amount) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-600">Commission courtier :</span>
                        <span class="font-semibold text-slate-900 tabular-nums">{{ formatXOF(bordereau.total_commission) }}</span>
                        <span v-if="bordereau.commission_pct != null" class="text-slate-500 text-xs">({{ Number(bordereau.commission_pct).toLocaleString('fr-FR', { minimumFractionDigits: 1 }) }} %)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-600">Prime à reverser :</span>
                        <span class="font-semibold text-slate-900 tabular-nums">{{ formatXOF(bordereau.prime_a_reverser) }}</span>
                    </div>
                </div>
            </div>

            <Link
                :href="route('bordereaux.index')"
                class="inline-flex items-center text-slate-600 text-xs hover:text-slate-900 hover:underline transition-colors"
            >
                ← Retour à la liste des bordereaux
            </Link>
        </div>
    </DashboardLayout>
</template>
