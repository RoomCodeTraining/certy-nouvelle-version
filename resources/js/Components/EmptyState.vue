<script setup>
import { Link } from "@inertiajs/vue3";

/**
 * État vide réutilisable pour listes / tableaux.
 * Props: title, description, ctaLabel, ctaHref, icon (optionnel: 'folder' | 'credit' | 'sparkles' | 'search')
 */
defineProps({
    title: { type: String, default: "Aucune donnée" },
    description: { type: String, default: null },
    ctaLabel: { type: String, default: null },
    ctaHref: { type: String, default: null },
    icon: { type: String, default: "folder" },
});

const iconPaths = {
    folder:
        "M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z",
    credit:
        "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
    sparkles:
        "M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z",
    search:
        "M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z",
    document:
        "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
};
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 sm:py-16 px-4 text-center">
        <div
            class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4"
        >
            <svg
                class="w-7 h-7"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    :d="iconPaths[icon] || iconPaths.folder"
                />
            </svg>
        </div>
        <h3 class="text-base font-medium text-slate-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-slate-500 max-w-sm">
            {{ description }}
        </p>
        <div v-if="ctaLabel && ctaHref" class="mt-6">
            <Link
                :href="ctaHref"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors"
            >
                {{ ctaLabel }}
            </Link>
        </div>
        <slot v-else-if="$slots.default" />
    </div>
</template>
