<script setup>
/**
 * Affiche un objet ou tableau d'objets en grille clé / valeur propre.
 * data: Object | Array (si array, affiche chaque item comme section ou ligne)
 */
const props = defineProps({
    data: {
        type: [Object, Array],
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
});

function isObject(val) {
    return val !== null && typeof val === 'object' && !Array.isArray(val);
}

function entries(obj) {
    if (!obj || typeof obj !== 'object') return [];
    return Object.entries(obj).filter(([, v]) => v !== undefined && v !== null);
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div v-if="title" class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
        </div>
        <div class="p-4">
            <template v-if="Array.isArray(data) && data.length">
                <div class="space-y-4">
                    <div
                        v-for="(item, idx) in data"
                        :key="idx"
                        class="border border-slate-200 rounded-lg overflow-hidden"
                    >
                        <template v-if="isObject(item)">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                                <div v-for="[k, v] in entries(item)" :key="k" class="flex flex-col">
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ String(k).replace(/_/g, ' ') }}</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ typeof v === 'object' ? JSON.stringify(v) : v }}</dd>
                                </div>
                            </dl>
                        </template>
                        <div v-else class="p-4 text-sm text-slate-700">{{ item }}</div>
                    </div>
                </div>
            </template>
            <template v-else-if="isObject(data)">
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="[k, v] in entries(data)" :key="k" class="flex flex-col py-2 border-b border-slate-100 last:border-0">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ String(k).replace(/_/g, ' ') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ isObject(v) ? JSON.stringify(v) : v }}</dd>
                    </div>
                </dl>
            </template>
            <p v-else class="text-sm text-slate-500">Aucune donnée.</p>
        </div>
    </div>
</template>
