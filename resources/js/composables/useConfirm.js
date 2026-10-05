import { reactive } from 'vue';

/*
 * Promise-based confirmation dialog: `if (await confirm({...})) { ... }`.
 * Rendered once by <ConfirmDialog> in each layout.
 */
export const confirmState = reactive({
    open: false,
    title: '',
    description: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    tone: 'danger',
    requireText: null,
    resolve: null,
});

export function confirm(options = {}) {
    if (confirmState.resolve) {
        confirmState.resolve(false);
    }

    return new Promise((resolve) => {
        Object.assign(confirmState, {
            title: 'Are you sure?',
            description: '',
            confirmLabel: 'Confirm',
            cancelLabel: 'Cancel',
            tone: 'danger',
            requireText: null,
            ...options,
            open: true,
            resolve,
        });
    });
}

export function settleConfirm(result) {
    confirmState.resolve?.(result);
    confirmState.resolve = null;
    confirmState.open = false;
}

export function useConfirm() {
    return { confirm };
}
