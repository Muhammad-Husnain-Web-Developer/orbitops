import { nextTick, onBeforeUnmount, watch } from 'vue';

/*
 * Shared behaviour for modal surfaces (Modal, Drawer, CommandPalette):
 * focus trap, Escape to close (top-most layer only), scroll lock and
 * restoring focus to whatever opened the dialog.
 */

const stack = [];
let lockCount = 0;

const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

function lockScroll() {
    if (lockCount++ === 0) {
        const scrollbar = window.innerWidth - document.documentElement.clientWidth;
        document.documentElement.style.overflow = 'hidden';
        document.documentElement.style.paddingRight = scrollbar ? `${scrollbar}px` : '';
    }
}

function unlockScroll() {
    if (--lockCount <= 0) {
        lockCount = 0;
        document.documentElement.style.overflow = '';
        document.documentElement.style.paddingRight = '';
    }
}

export function useDialog(openRef, panelRef, { onClose, initialFocus } = {}) {
    const id = Symbol('dialog');
    let previouslyFocused = null;

    function onKeydown(event) {
        if (stack[stack.length - 1] !== id) return;

        if (event.key === 'Escape') {
            event.stopPropagation();
            onClose?.();

            return;
        }

        if (event.key === 'Tab' && panelRef.value) {
            const nodes = [...panelRef.value.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);

            if (!nodes.length) {
                event.preventDefault();
                panelRef.value.focus();

                return;
            }

            const first = nodes[0];
            const last = nodes[nodes.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }

    async function activate() {
        previouslyFocused = document.activeElement;
        stack.push(id);
        lockScroll();
        document.addEventListener('keydown', onKeydown, true);

        await nextTick();

        const target =
            initialFocus === 'panel'
                ? panelRef.value
                : (initialFocus && panelRef.value?.querySelector(initialFocus)) || panelRef.value?.querySelector('[autofocus]') || panelRef.value?.querySelector(FOCUSABLE) || panelRef.value;
        target?.focus({ preventScroll: true });
    }

    function deactivate() {
        const index = stack.indexOf(id);

        if (index === -1) return;

        stack.splice(index, 1);
        unlockScroll();
        document.removeEventListener('keydown', onKeydown, true);

        if (previouslyFocused instanceof HTMLElement && document.contains(previouslyFocused)) {
            previouslyFocused.focus({ preventScroll: true });
        }
    }

    watch(openRef, (open) => (open ? activate() : deactivate()), { immediate: true, flush: 'post' });

    onBeforeUnmount(deactivate);
}
