<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TableFilters from '@/Components/TableFilters.vue';
import Paginator from '@/Components/Paginator.vue';
import { route } from '@/route';
import { formatDate } from '@/utils/formatDate';

const props = defineProps({
    bordereaux: Object,
    companies: Array,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Bordereaux' },
];

const statusLabels = {
    draft: 'Brouillon',
    validated: 'Validé',
    submitted: 'Soumis',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    paid: 'Payé',
};

/** Référence bordereau : BR- + 11 caractères alphanumériques majuscules. */
function bordereauReference(row) {
    return row.reference ?? '—';
}

const queryParams = computed(() => ({
    search: props.filters?.search ?? '',
    company_id: props.filters?.company_id ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    per_page: props.bordereaux?.per_page ?? 25,
}));

const hasActiveFilters = computed(() => !!(
    props.filters?.search || props.filters?.company_id || props.filters?.date_from || props.filters?.date_to
));

function formatXOF(value) {
    if (value == null) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' F CFA';
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Bordereaux">
                <template #actions>
                    <Link
                        :href="route('bordereaux.create')"
                        class="inline-flex items-center justify-center w-full sm:w-auto min-h-[44px] sm:min-h-0 px-4 py-3 sm:py-2 bg-slate-900 text-white text-sm font-medium rounded-xl sm:rounded-lg hover:bg-slate-800"
                    >
                        Nouveau bordereau
                    </Link>
                </template>
            </PageHeader>
        </template>

        <p class="text-slate-600 text-sm mb-4">
            Bordereaux par compagnie et période (du → au). Sont inclus les contrats dont la date de création est dans cette période. Référence au format BR- + 11 caractères alphanumériques majuscules.
        </p>

        <TableFilters
            action="/bordereaux"
            reset-href="/bordereaux"
            :has-active-filters="hasActiveFilters"
        >
            <input
                type="search"
                name="search"
                :value="filters?.search"
                placeholder="Référence (ex. BR-...)"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-48 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
            />
            <select name="company_id" class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-56 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none">
                <option value="">Toutes les compagnies</option>
                <option
                    v-for="c in companies"
                    :key="c.id"
                    :value="c.id"
                    :selected="filters?.company_id === String(c.id)"
                >
                    {{ c.name }}{{ c.code ? ` (${c.code})` : '' }}
                </option>
            </select>
            <div class="flex items-center gap-2">
                <label class="text-sm text-slate-600 whitespace-nowrap">Période</label>
                <input
                    type="date"
                    name="date_from"
                    :value="filters?.date_from"
                    placeholder="Du"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-36 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    title="Du"
                />
                <span class="text-slate-400">→</span>
                <input
                    type="date"
                    name="date_to"
                    :value="filters?.date_to"
                    placeholder="Au"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-36 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    title="Au"
                />
            </div>
            <input type="hidden" name="per_page" :value="bordereaux?.per_page ?? 25" />
        </TableFilters>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <Link
                    v-for="b in (bordereaux?.data ?? [])"
                    :key="b.id"
                    :href="route('bordereaux.show', b.id)"
                    class="block p-4 active:bg-slate-50/80 transition-colors"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-medium text-slate-900">{{ bordereauReference(b) }}</p>
                            <p class="text-sm text-slate-700 mt-0.5">{{ b.company?.name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ formatDate(b.period_start) }} → {{ formatDate(b.period_end) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            <span
                                :class="[
                                    'inline-flex px-2.5 py-1 rounded-full text-xs font-medium',
                                    b.status === 'validated' || b.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : b.status === 'approved' ? 'bg-sky-100 text-sky-800' : b.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800',
                                ]"
                            >
                                {{ statusLabels[b.status] ?? b.status ?? '—' }}
                            </span>
                            <p class="text-sm font-medium text-slate-900">{{ formatXOF(b.total_amount) }}</p>
                        </div>
                    </div>
                </Link>
                <div
                    v-if="!(bordereaux?.data?.length)"
                    class="py-10 px-4 text-center text-slate-500 text-sm"
                >
                    Aucun bordereau.
                    <Link :href="route('bordereaux.create')" class="text-sky-600 hover:underline block mt-2">Créer un bordereau</Link>
                </div>
            </div>

            <!-- Desktop : tableau -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Référence</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Compagnie</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Période du</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">au</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Montant total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Commission</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr
                            v-for="b in (bordereaux?.data ?? [])"
                            :key="b.id"
                            class="hover:bg-slate-50/50"
                        >
                            <td class="px-4 py-3 font-mono text-sm text-slate-900">{{ bordereauReference(b) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ b.company?.name ?? '—' }}{{ b.company?.code ? ` (${b.company.code})` : '' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ formatDate(b.period_start) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ formatDate(b.period_end) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ formatXOF(b.total_amount) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">
                                {{ formatXOF(b.total_commission) }}<template v-if="b.commission_pct != null"> ({{ Number(b.commission_pct).toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }} %)</template>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        b.status === 'validated' || b.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : b.status === 'approved' ? 'bg-sky-100 text-sky-800' : b.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800',
                                    ]"
                                >
                                    {{ statusLabels[b.status] ?? b.status ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('bordereaux.show', b.id)"
                                    class="text-sky-600 hover:underline text-sm font-medium"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!(bordereaux?.data?.length)">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                Aucun bordereau. <Link :href="route('bordereaux.create')" class="text-sky-600 hover:underline">Créer un bordereau</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Paginator
                v-if="bordereaux"
                :paginator="bordereaux"
                :query-params="queryParams"
            />
        </div>
    </DashboardLayout>
</template>
