<script setup>
import { nextTick, onBeforeUnmount, provide, ref, useId } from 'vue';

const props = defineProps({
    align: { type: String, default: 'start' },
    width: { type: String, default: 'w-56' },
    label: { type: String, default: 'Menu' },
});

const emit = defineEmits(['open', 'close']);

const open = ref(false);
const trigger = ref(null);
const menu = ref(null);
const position = ref({ top: 0, left: 0, placement: 'bottom' });
const id = useId();

function items() {
    return [...(menu.value?.querySelectorAll('[role="menuitem"]:not([aria-disabled="true"])') ?? [])];
}

function place() {
    const anchor = trigger.value?.firstElementChild ?? trigger.value;
    if (!anchor || !menu.value) return;

    const rect = anchor.getBoundingClientRect();
    const menuRect = menu.value.getBoundingClientRect();
    const gap = 6;
    const below = window.innerHeight - rect.bottom;
    const placement = below < menuRect.height + 16 && rect.top > menuRect.height + 16 ? 'top' : 'bottom';

    let left = props.align === 'end' ? rect.right - menuRect.width : rect.left;
    left = Math.min(Math.max(8, left), window.innerWidth - menuRect.width - 8);

    position.value = {
        top: placement === 'bottom' ? rect.bottom + gap : rect.top - menuRect.height - gap,
        left,
        placement,
    };
}

function onOutside(event) {
    if (!menu.value?.contains(event.target) && !trigger.value?.contains(event.target)) {
        close(false);
    }
}

async function show(focusFirst = false) {
    open.value = true;
    emit('open');
    await nextTick();
    place();
    document.addEventListener('pointerdown', onOutside, true);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);

    if (focusFirst) {
        items()[0]?.focus();
    } else {
        menu.value?.focus({ preventScroll: true });
    }
}

function close(restoreFocus = true) {
    if (!open.value) return;

    open.value = false;
    emit('close');
    document.removeEventListener('pointerdown', onOutside, true);
    window.removeEventListener('resize', place);
    window.removeEventListener('scroll', place, true);

    if (restoreFocus) {
        (trigger.value?.querySelector('button, a, [tabindex]') ?? trigger.value)?.focus({ preventScroll: true });
    }
}

function toggle(event) {
    open.value ? close() : show(event?.detail === 0);
}

function onKeydown(event) {
    const list = items();
    const index = list.indexOf(document.activeElement);

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            list[(index + 1) % list.length]?.focus();
            break;
        case 'ArrowUp':
            event.preventDefault();
            list[(index - 1 + list.length) % list.length]?.focus();
            break;
        case 'Home':
            event.preventDefault();
            list[0]?.focus();
            break;
        case 'End':
            event.preventDefault();
            list[list.length - 1]?.focus();
            break;
        case 'Escape':
            event.preventDefault();
            event.stopPropagation();
            close();
            break;
        case 'Tab':
            close(false);
            break;
    }
}

function onTriggerKeydown(event) {
    if (['ArrowDown', 'Enter', ' '].includes(event.key) && !open.value) {
        event.preventDefault();
        show(true);
    }
}

provide('dropdown', { close });

onBeforeUnmount(() => close(false));

defineExpose({ show, close });
</script>

<template>
    <div ref="trigger" class="relative inline-flex" @keydown="onTriggerKeydown">
        <slot name="trigger" :open="open" :toggle="toggle" :attrs="{ 'aria-haspopup': 'menu', 'aria-expanded': open, 'aria-controls': open ? id : undefined, onClick: toggle }" />
    </div>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-150 ease-[var(--ease-out-expo)]"
            enter-from-class="opacity-0 scale-[0.97]"
            leave-active-class="duration-100 ease-in"
            leave-to-class="opacity-0 scale-[0.97]"
        >
            <div
                v-if="open"
                :id="id"
                ref="menu"
                role="menu"
                :aria-label="label"
                tabindex="-1"
                class="fixed z-[60] max-h-[min(70vh,28rem)] overflow-y-auto rounded-xl border border-line bg-elevated p-1 shadow-overlay outline-none"
                :class="[width, position.placement === 'top' ? 'origin-bottom' : 'origin-top']"
                :style="{ top: `${position.top}px`, left: `${position.left}px` }"
                @keydown="onKeydown"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </Teleport>
</template>
