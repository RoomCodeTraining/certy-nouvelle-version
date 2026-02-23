<script setup>
/**
 * Skeleton pour une carte KPI du tableau de bord.
 */
defineProps({
    label: { type: String, default: "" },
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
        <p
            v-if="label"
            class="text-sm text-slate-500 mb-2 h-4 flex items-center"
        >
            {{ label }}
        </p>
        <p v-else class="text-sm text-slate-500 mb-2">
            <span
                class="inline-block h-4 w-24 rounded bg-slate-200 animate-pulse"
            />
        </p>
        <p class="h-7 w-20 rounded bg-slate-200 animate-pulse" />
    </div>
</template>
