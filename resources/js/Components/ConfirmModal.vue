<script setup>
import { watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmer' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmer' },
    cancelLabel: { type: String, default: 'Annuler' },
    variant: { type: String, default: 'danger' }, // danger | default
});

const emit = defineEmits(['confirm', 'cancel']);

function onConfirm() {
    emit('confirm');
}

function onCancel() {
    emit('cancel');
}

function onBackdrop(e) {
    if (e.target === e.currentTarget) onCancel();
}

// Fermer avec Escape quand le modal est ouvert
watch(() => props.open, (isOpen) => {
    if (!isOpen) return;
    const handler = (e) => {
        if (e.key === 'Escape') onCancel();
    };
    document.addEventListener('keydown', handler);
    return () => {
        document.removeEventListener('keydown', handler);
    };
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                aria-describedby="confirm-message"
                @click="onBackdrop"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="open"
                        class="w-full max-w-md rounded-xl bg-white border border-slate-200"
                        @click.stop
                    >
                        <div class="p-6">
                            <h2
                                id="confirm-title"
                                class="text-lg font-semibold text-slate-900"
                            >
                                {{ title }}
                            </h2>
                            <p
                                v-if="message"
                                id="confirm-message"
                                class="mt-2 text-sm text-slate-600 leading-relaxed"
                            >
                                {{ message }}
                            </p>
                        </div>
                        <div class="flex items-center justify-end gap-3 px-6 pb-6">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                                @click="onCancel"
                            >
                                {{ cancelLabel }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                                :class="variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-900 hover:bg-slate-800'"
                                @click="onConfirm"
                            >
                                {{ confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
