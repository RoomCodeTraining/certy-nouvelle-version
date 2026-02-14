<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
const visible = ref(false);
const message = ref(null);
const type = ref(null);
let dismissTimer = null;

function show(flash) {
    if (flash?.success) {
        message.value = flash.success;
        type.value = 'success';
        visible.value = true;
        scheduleDismiss();
    } else if (flash?.error) {
        message.value = flash.error;
        type.value = 'error';
        visible.value = true;
        scheduleDismiss();
    }
}

function scheduleDismiss() {
    if (dismissTimer) clearTimeout(dismissTimer);
    dismissTimer = setTimeout(() => {
        visible.value = false;
        dismissTimer = null;
    }, 5000);
}

function close() {
    visible.value = false;
    if (dismissTimer) {
        clearTimeout(dismissTimer);
        dismissTimer = null;
    }
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash && (flash.success || flash.error)) {
            show(flash);
        }
    },
    { immediate: true }
);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-4"
    >
        <div
            v-if="visible && message"
            class="fixed bottom-4 right-4 z-50 w-full max-w-sm"
            role="alert"
        >
            <div
                :class="[
                    'flex items-center justify-between gap-3 rounded-xl border-2 px-4 py-3',
                    type === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : 'border-red-200 bg-red-50 text-red-800',
                ]"
            >
                <p class="text-sm font-medium">{{ message }}</p>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1 transition-colors hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-inset"
                    :class="type === 'success' ? 'focus:ring-emerald-400' : 'focus:ring-red-400'"
                    aria-label="Fermer"
                    @click="close"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>
