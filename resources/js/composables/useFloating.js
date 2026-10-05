import { nextTick, onBeforeUnmount, ref } from 'vue';

/**
 * Position a teleported panel next to its trigger, flipping above when there
 * is no room below and staying inside the viewport. Closes on outside click.
 */
export function useFloating(trigger, panel, { align = 'start', gap = 6, onDismiss } = {}) {
    const position = ref({ top: 0, left: 0, placement: 'bottom' });

    function place() {
        const anchor = trigger.value?.firstElementChild ?? trigger.value;
        if (!anchor || !panel.value) return;

        const rect = anchor.getBoundingClientRect();
        const box = panel.value.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom;
        const placement = below < box.height + 16 && rect.top > box.height + 16 ? 'top' : 'bottom';

        let left = align === 'end' ? rect.right - box.width : align === 'center' ? rect.left + rect.width / 2 - box.width / 2 : rect.left;
        left = Math.min(Math.max(8, left), window.innerWidth - box.width - 8);

        position.value = { top: placement === 'bottom' ? rect.bottom + gap : rect.top - box.height - gap, left, placement };
    }

    function onOutside(event) {
        if (!panel.value?.contains(event.target) && !trigger.value?.contains(event.target)) {
            onDismiss?.();
        }
    }

    async function attach() {
        await nextTick();
        place();
        document.addEventListener('pointerdown', onOutside, true);
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);
    }

    function detach() {
        document.removeEventListener('pointerdown', onOutside, true);
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
    }

    onBeforeUnmount(detach);

    return { position, place, attach, detach };
}
