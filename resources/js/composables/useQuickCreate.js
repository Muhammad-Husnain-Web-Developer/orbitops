import { reactive } from 'vue';

/*
 * Global "quick create" state so any screen (topbar menu, command palette,
 * empty states, keyboard shortcuts) can open the same create forms.
 */
export const quickCreate = reactive({ kind: null, defaults: {} });

export function openQuickCreate(kind, defaults = {}) {
    quickCreate.defaults = defaults;
    quickCreate.kind = kind;
}

export function closeQuickCreate() {
    quickCreate.kind = null;
    quickCreate.defaults = {};
}
