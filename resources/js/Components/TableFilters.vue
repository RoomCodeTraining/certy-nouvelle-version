<script setup>
import { Link } from '@inertiajs/vue3';

/**
 * Barre de recherche et filtres pour datatable (GET, préserve l’URL pour pagination).
 * action: URL du formulaire (ex: /clients)
 * resetHref: URL pour "Réinitialiser" (ex: /clients sans query)
 */
const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    resetHref: {
        type: String,
        default: null,
    },
    hasActiveFilters: {
        type: Boolean,
        default: false,
    },
});

const resetUrl = props.resetHref ?? props.action;
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 mb-4">
        <form :action="action" method="get" class="flex flex-col sm:flex-row sm:items-end gap-4">
            <slot />
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors"
                >
                    Rechercher
                </button>
                <Link
                    v-if="hasActiveFilters || resetHref !== action"
                    :href="resetUrl"
                    class="inline-flex items-center px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                    Réinitialiser
                </Link>
            </div>
        </form>
    </div>
</template>
