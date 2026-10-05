<script setup>
import { ref, useId } from 'vue';
import { useFloating } from '@/composables/useFloating';

const props = defineProps({
    align: { type: String, default: 'end' },
    width: { type: String, default: 'w-96' },
    label: { type: String, required: true },
});

const emit = defineEmits(['open', 'close']);

const open = ref(false);
const trigger = ref(null);
const panel = ref(null);
const id = useId();

const { position, attach, detach } = useFloating(trigger, panel, { align: props.align, gap: 8, onDismiss: () => close(false) });

async function show() {
    open.value = true;
    emit('open');
    await attach();
    panel.value?.focus({ preventScroll: true });
}

function close(restoreFocus = true) {
    if (!open.value) return;
    open.value = false;
    emit('close');
    detach();

    if (restoreFocus) {
        trigger.value?.querySelector('button')?.focus({ preventScroll: true });
    }
}

function toggle() {
    open.value ? close() : show();
}

defineExpose({ show, close, toggle });
</script>

<template>
    <div ref="trigger" class="relative inline-flex">
        <slot name="trigger" :open="open" :attrs="{ 'aria-expanded': open, 'aria-controls': open ? id : undefined, 'aria-haspopup': 'dialog', onClick: toggle }" />
    </div>
    <Teleport to="body">
        <Transition enter-active-class="duration-150 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0 scale-[0.97]" leave-active-class="duration-100 ease-in" leave-to-class="opacity-0 scale-[0.97]">
            <div
                v-if="open"
                :id="id"
                ref="panel"
                role="dialog"
                :aria-label="label"
                tabindex="-1"
                class="fixed z-[60] max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl border border-line bg-elevated shadow-overlay outline-none"
                :class="[width, position.placement === 'top' ? 'origin-bottom' : 'origin-top-right']"
                :style="{ top: `${position.top}px`, left: `${position.left}px` }"
                @keydown.esc.stop.prevent="close()"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </Teleport>
</template>
