import { reactive } from 'vue';

const state = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    variant: 'danger', // 'danger' | 'default'
    resolve: null,
});

export function useConfirm() {
    function confirm(options = {}) {
        return new Promise((resolve) => {
            state.title = options.title ?? 'Confirmer';
            state.message = options.message ?? '';
            state.confirmLabel = options.confirmLabel ?? 'Confirmer';
            state.cancelLabel = options.cancelLabel ?? 'Annuler';
            state.variant = options.variant ?? 'danger';
            state.resolve = (result) => {
                state.open = false;
                resolve(result);
            };
            state.open = true;
        });
    }

    function close(result = false) {
        if (state.resolve) state.resolve(result);
    }

    return { state, confirm, close };
}
