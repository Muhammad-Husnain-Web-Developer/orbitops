<script setup>
import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from '@lucide/vue';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss, pause, resume } = useToast();

const icons = { success: CircleCheck, error: CircleAlert, warning: TriangleAlert, info: Info };
const tones = { success: 'text-success', error: 'text-danger', warning: 'text-warning', info: 'text-info' };
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-3 z-[70] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:top-auto sm:right-5 sm:bottom-5 sm:items-end" aria-live="polite" aria-relevant="additions">
        <TransitionGroup
            enter-active-class="duration-300 ease-[var(--ease-out-expo)]"
            enter-from-class="-translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
            leave-active-class="duration-200 ease-in absolute"
            leave-to-class="opacity-0 scale-95"
            move-class="duration-300 ease-[var(--ease-out-expo)]"
        >
            <div
                v-for="item in toasts"
                :key="item.id"
                :role="item.type === 'error' ? 'alert' : 'status'"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-line bg-elevated p-3.5 pr-2.5 shadow-overlay"
                @mouseenter="pause(item.id)"
                @mouseleave="resume(item.id)"
                @focusin="pause(item.id)"
                @focusout="resume(item.id)"
            >
                <component :is="icons[item.type] ?? Info" class="mt-px size-[18px] shrink-0" :class="tones[item.type]" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="text-body font-medium text-ink">{{ item.message }}</p>
                    <p v-if="item.description" class="mt-0.5 text-small text-ink-3">{{ item.description }}</p>
                    <button v-if="item.action" type="button" class="mt-2 text-small font-medium text-accent-text hover:underline" @click="item.action.onClick(); dismiss(item.id)">
                        {{ item.action.label }}
                    </button>
                </div>
                <button type="button" class="rounded-md p-1 text-ink-3 transition-colors hover:bg-hover hover:text-ink" aria-label="Dismiss notification" @click="dismiss(item.id)">
                    <X class="size-3.5" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
