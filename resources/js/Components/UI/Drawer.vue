<script setup>
import { X } from '@lucide/vue';
import { ref, useId } from 'vue';
import { useDialog } from '@/composables/useDialog';

const open = defineModel('open', { type: Boolean, default: false });

defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    width: { type: String, default: 'max-w-xl' },
});

const emit = defineEmits(['close']);

const panel = ref(null);
const id = useId();

function close() {
    open.value = false;
    emit('close');
}

useDialog(open, panel, { onClose: close });
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" leave-active-class="duration-200 ease-in" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 bg-overlay" aria-hidden="true" @click="close" />
        </Transition>
        <Transition
            enter-active-class="duration-300 ease-[var(--ease-out-expo)]"
            enter-from-class="translate-x-full"
            leave-active-class="duration-200 ease-in"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="open"
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="title ? `${id}-title` : undefined"
                tabindex="-1"
                class="fixed inset-y-0 right-0 z-50 flex w-full flex-col border-l border-line bg-elevated shadow-overlay outline-none"
                :class="width"
            >
                <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                    <slot name="header">
                        <div class="min-w-0">
                            <h2 :id="`${id}-title`" class="text-h3 text-ink">{{ title }}</h2>
                            <p v-if="description" class="mt-0.5 text-small text-ink-3">{{ description }}</p>
                        </div>
                    </slot>
                    <div class="flex shrink-0 items-center gap-1">
                        <slot name="actions" />
                        <button type="button" class="rounded-md p-1.5 text-ink-3 transition-colors hover:bg-hover hover:text-ink" aria-label="Close panel" @click="close">
                            <X class="size-4" />
                        </button>
                    </div>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <slot />
                </div>
                <footer v-if="$slots.footer" class="safe-bottom border-t border-line px-5 py-3 sm:px-6">
                    <slot name="footer" />
                </footer>
            </div>
        </Transition>
    </Teleport>
</template>
