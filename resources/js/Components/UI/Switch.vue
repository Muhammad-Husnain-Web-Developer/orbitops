<script setup>
import { useId } from 'vue';

const model = defineModel({ type: Boolean, default: false });

defineProps({
    label: { type: String, default: null },
    description: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const id = useId();
</script>

<template>
    <div class="flex items-start justify-between gap-4">
        <div v-if="label || description" class="min-w-0">
            <label :for="id" class="cursor-pointer text-label text-ink">{{ label }}</label>
            <p v-if="description" class="mt-0.5 text-small text-ink-3">{{ description }}</p>
        </div>
        <button
            :id="id"
            type="button"
            role="switch"
            :aria-checked="model"
            :aria-label="label ? undefined : ($attrs['aria-label'] ?? 'Toggle')"
            :disabled="disabled"
            class="relative inline-flex shrink-0 cursor-pointer items-center rounded-full border transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50"
            :class="[model ? 'border-accent bg-accent' : 'border-line-strong bg-subtle', size === 'sm' ? 'h-4.5 w-8' : 'h-5.5 w-10']"
            @click="model = !model"
        >
            <span
                class="pointer-events-none inline-block rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform duration-200 ease-[var(--ease-spring)]"
                :class="[size === 'sm' ? 'size-3.5' : 'size-4.5', model ? (size === 'sm' ? 'translate-x-[15px]' : 'translate-x-[19px]') : 'translate-x-[1px]']"
            />
        </button>
    </div>
</template>
