<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Pagination côté serveur pour gros volumes.
 * paginator: objet Laravel (current_page, last_page, total, from, to, per_page, path, first_page_url, prev_page_url, next_page_url, last_page_url)
 * queryParams: object des paramètres GET actuels (search, type_assure, per_page, etc.) pour construire les liens des numéros de page
 */
const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
    queryParams: {
        type: Object,
        default: () => ({}),
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
});

const total = computed(() => props.paginator.total ?? 0);
const currentPage = computed(() => props.paginator.current_page ?? 1);
const lastPage = computed(() => props.paginator.last_page ?? 1);
const perPage = computed(() => props.paginator.per_page ?? 15);
const from = computed(() => props.paginator.from);
const to = computed(() => props.paginator.to);
const path = computed(() => props.paginator.path ?? '');

const hasPages = computed(() => lastPage.value > 1);
const hasTotal = computed(() => total.value > 0);
const summaryText = computed(() => {
    if (!hasTotal.value) return 'Aucun résultat';
    if (from.value != null && to.value != null) return `Affichage ${from.value} à ${to.value} sur ${total.value} résultat${total.value > 1 ? 's' : ''}`;
    return `${total.value} résultat${total.value > 1 ? 's' : ''}`;
});

function buildPageUrl(page) {
    const params = { ...props.queryParams, page: String(page), per_page: String(perPage.value) };
    const search = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => { if (v != null && v !== '') search.set(k, v); });
    const qs = search.toString();
    return qs ? `${path.value}?${qs}` : `${path.value}?page=${page}`;
}

const pageNumbers = computed(() => {
    const last = lastPage.value;
    const current = currentPage.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [];
    if (current <= 4) {
        pages.push(1, 2, 3, 4, 5, '…', last);
    } else if (current >= last - 3) {
        pages.push(1, '…', last - 4, last - 3, last - 2, last - 1, last);
    } else {
        pages.push(1, '…', current - 1, current, current + 1, '…', last);
    }
    return pages;
});

function goToPerPage(newPerPage) {
    const params = { ...props.queryParams, page: 1, per_page: newPerPage };
    router.get(path.value, params, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 px-4 py-3 border-t border-slate-200 bg-slate-50/50">
        <div class="flex flex-wrap items-center gap-4">
            <p class="text-sm text-slate-600">
                {{ summaryText }}
            </p>
            <div v-if="hasPages && hasTotal" class="flex items-center gap-2">
                <label for="per-page" class="text-sm text-slate-500">Par page</label>
                <select
                    id="per-page"
                    :value="paginator.per_page"
                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    @change="(e) => goToPerPage(Number(e.target.value))"
                >
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>
        </div>
        <nav v-if="hasPages" class="flex items-center gap-1" aria-label="Pagination">
            <Link
                v-if="paginator.prev_page_url"
                :href="paginator.prev_page_url"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                preserve-scroll
            >
                Précédent
            </Link>
            <template v-for="(num, i) in pageNumbers" :key="i">
                <span v-if="num === '…'" class="px-2 py-2 text-slate-400">…</span>
                <Link
                    v-else
                    :href="buildPageUrl(num)"
                    class="min-w-9 inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                    :class="num === currentPage ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    preserve-scroll
                >
                    {{ num }}
                </Link>
            </template>
            <Link
                v-if="paginator.next_page_url"
                :href="paginator.next_page_url"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                preserve-scroll
            >
                Suivant
            </Link>
        </nav>
    </div>
</template>
