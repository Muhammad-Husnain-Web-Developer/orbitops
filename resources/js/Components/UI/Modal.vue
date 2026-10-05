<script setup>
import { X } from '@lucide/vue';
import { ref, useId } from 'vue';
import { useDialog } from '@/composables/useDialog';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    size: { type: String, default: 'md' },
    closable: { type: Boolean, default: true },
    initialFocus: { type: String, default: null },
});

const emit = defineEmits(['close']);

const panel = ref(null);
const id = useId();

const sizes = { sm: 'sm:max-w-sm', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl', xl: 'sm:max-w-4xl' };

function close() {
    if (!props.closable) return;
    open.value = false;
    emit('close');
}

useDialog(open, panel, { onClose: close, initialFocus: props.initialFocus });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 bg-overlay backdrop-blur-[2px]" aria-hidden="true" @click="close" />
        </Transition>
        <Transition
            enter-active-class="duration-300 ease-[var(--ease-out-expo)]"
            enter-from-class="translate-y-6 opacity-0 sm:translate-y-2 sm:scale-[0.98]"
            leave-active-class="duration-150 ease-in"
            leave-to-class="translate-y-4 opacity-0 sm:translate-y-1 sm:scale-[0.98]"
        >
            <div v-if="open" class="pointer-events-none fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6">
                <div
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="title ? `${id}-title` : undefined"
                    :aria-describedby="description ? `${id}-description` : undefined"
                    tabindex="-1"
                    class="pointer-events-auto flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl border border-line bg-elevated shadow-overlay outline-none sm:rounded-2xl"
                    :class="sizes[size] ?? sizes.md"
                >
                    <div class="mx-auto mt-2 h-1 w-10 shrink-0 rounded-full bg-line-strong sm:hidden" aria-hidden="true" />
                    <header v-if="title || $slots.header" class="flex items-start justify-between gap-4 px-5 pt-4 sm:px-6 sm:pt-5">
                        <slot name="header">
                            <div class="min-w-0">
                                <h2 :id="`${id}-title`" class="text-h3 text-ink">{{ title }}</h2>
                                <p v-if="description" :id="`${id}-description`" class="mt-1 text-small text-ink-3">{{ description }}</p>
                            </div>
                        </slot>
                        <button v-if="closable" type="button" class="-mt-1 -mr-2 rounded-md p-1.5 text-ink-3 transition-colors hover:bg-hover hover:text-ink" aria-label="Close dialog" @click="close">
                            <X class="size-4" />
                        </button>
                    </header>
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                        <slot :close="close" />
                    </div>
                    <footer v-if="$slots.footer" class="safe-bottom flex flex-col-reverse gap-2 border-t border-line bg-surface/60 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                        <slot name="footer" :close="close" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
