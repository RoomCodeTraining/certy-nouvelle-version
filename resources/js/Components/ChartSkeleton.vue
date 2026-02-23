<script setup>
/**
 * Skeleton pour la zone des graphiques (Vue analytique).
 */
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6 overflow-hidden">
        <div class="space-y-6">
            <div class="h-5 w-32 rounded bg-slate-200 animate-pulse" />
            <div class="h-48 sm:h-64 rounded-lg bg-slate-100 animate-pulse" />
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div v-for="i in 3" :key="i" class="h-40 rounded-lg bg-slate-100 animate-pulse" />
            </div>
        </div>
    </div>
</template>
