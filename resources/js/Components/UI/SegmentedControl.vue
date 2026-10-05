<script setup>
import { useId } from 'vue';

const model = defineModel({ type: [String, Number, Boolean], default: null });

defineProps({
    options: { type: Array, required: true },
    label: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const name = `segmented-${useId()}`;
</script>

<template>
    <div role="radiogroup" :aria-label="label" class="inline-flex items-center gap-0.5 rounded-lg border border-line bg-subtle p-0.5">
        <label
            v-for="option in options"
            :key="String(option.value)"
            class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-md font-medium transition-colors duration-150 has-focus-visible:outline-2 has-focus-visible:outline-accent"
            :class="[
                size === 'sm' ? 'h-7 px-2.5 text-caption' : 'h-8 px-3 text-small',
                model === option.value ? 'bg-surface text-ink shadow-card' : 'text-ink-3 hover:text-ink',
            ]"
        >
            <input v-model="model" type="radio" :name="name" :value="option.value" class="sr-only" />
            <component :is="option.icon" v-if="option.icon" class="size-3.5" aria-hidden="true" />
            {{ option.label }}
            <span v-if="option.badge" class="rounded-full bg-success/15 px-1.5 py-px text-[0.6875rem] font-semibold text-success">{{ option.badge }}</span>
        </label>
    </div>
</template>
