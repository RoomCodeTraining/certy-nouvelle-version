<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    /** Tooltip text */
    label: { type: String, required: true },
    /** Link href (Inertia) – use with Link */
    to: { type: [String, Object], default: null },
    /** External URL – renders <a> */
    href: { type: String, default: null },
    /** Visual variant */
    variant: { type: String, default: 'default' }, // default | danger | warning
    /** Icon name: edit, trash, eye, download, external, x, more, refresh */
    icon: { type: String, default: 'edit' },
    /** When href is set, open in new tab */
    external: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const iconPaths = {
    edit: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V7a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    eye: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    download: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    external: 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
    x: 'M6 18L18 6M6 6l12 12',
    more: 'M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z',
    refresh: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
};

const path = iconPaths[props.icon] || iconPaths.edit;
</script>

<template>
    <span class="relative group inline-flex">
        <component
            :is="to ? Link : (href ? 'a' : 'button')"
            :href="(to || href) || undefined"
            :target="href && external ? '_blank' : undefined"
            :rel="href && external ? 'noopener noreferrer' : undefined"
            type="button"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
            :class="[
                variant === 'danger' ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : '',
                variant === 'warning' ? 'text-amber-600 hover:text-amber-700 hover:bg-amber-50' : '',
                variant === 'default' ? 'text-slate-500 hover:text-slate-900 hover:bg-slate-100' : '',
            ]"
            @click="(e) => { if (!to && !href) emit('click', e); }"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" :d="path" />
            </svg>
        </component>
        <span
            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 text-xs font-medium text-white bg-slate-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-150 whitespace-nowrap z-50"
            role="tooltip"
        >
            {{ label }}
        </span>
    </span>
</template>
