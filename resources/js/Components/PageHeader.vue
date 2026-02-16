<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * breadcrumbs: Array<{ label: string, href?: string }>
 * title: string (optional, defaults to last breadcrumb label)
 */
const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: '',
    },
});

const displayTitle = computed(() => {
    if (props.title) return props.title;
    const last = props.breadcrumbs[props.breadcrumbs.length - 1];
    return last?.label ?? '';
});
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="min-w-0">
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                <template v-for="(crumb, i) in breadcrumbs" :key="i">
                    <span v-if="i > 0" class="text-slate-300 select-none" aria-hidden="true">/</span>
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="hover:text-slate-700 transition-colors truncate max-w-[140px] sm:max-w-none"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="text-slate-900 font-medium truncate">{{ crumb.label }}</span>
                </template>
            </nav>
            <h1 class="text-lg font-semibold text-slate-900 tracking-tight truncate">
                {{ displayTitle }}
            </h1>
        </div>
        <div v-if="$slots.actions" class="w-full sm:w-auto flex items-center gap-2 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
