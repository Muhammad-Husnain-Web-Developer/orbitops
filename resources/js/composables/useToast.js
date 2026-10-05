import { reactive } from 'vue';

/*
 * One global toast store. Anything can call `toast()`; <Toaster> renders it.
 * Toasts pause while hovered or focused so they can be read.
 */
const state = reactive({ items: [] });
const timers = new Map();
let nextId = 0;

function schedule(item) {
    clearTimeout(timers.get(item.id));

    if (item.duration > 0) {
        item.startedAt = Date.now();
        timers.set(item.id, setTimeout(() => dismiss(item.id), item.remaining));
    }
}

export function toast(message, { type = 'success', description = null, duration = 4800, action = null } = {}) {
    const item = reactive({ id: ++nextId, message, type, description, action, duration, remaining: duration, startedAt: 0 });

    state.items.push(item);

    if (state.items.length > 4) {
        dismiss(state.items[0].id);
    }

    schedule(item);

    return item.id;
}

toast.success = (message, options) => toast(message, { ...options, type: 'success' });
toast.error = (message, options) => toast(message, { ...options, type: 'error', duration: options?.duration ?? 7000 });
toast.warning = (message, options) => toast(message, { ...options, type: 'warning' });
toast.info = (message, options) => toast(message, { ...options, type: 'info' });

export function dismiss(id) {
    clearTimeout(timers.get(id));
    timers.delete(id);

    const index = state.items.findIndex((item) => item.id === id);

    if (index !== -1) {
        state.items.splice(index, 1);
    }
}

export function pause(id) {
    const item = state.items.find((entry) => entry.id === id);

    if (item?.duration > 0) {
        clearTimeout(timers.get(id));
        item.remaining = Math.max(1200, item.remaining - (Date.now() - item.startedAt));
    }
}

export function resume(id) {
    const item = state.items.find((entry) => entry.id === id);

    if (item) {
        schedule(item);
    }
}

export function useToast() {
    return { toasts: state.items, toast, dismiss, pause, resume };
}
