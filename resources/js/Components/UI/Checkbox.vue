<script setup>
import { Check } from '@lucide/vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [Boolean, Array], default: false });

defineProps({
    value: { type: [String, Number], default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <label class="group inline-flex cursor-pointer items-start gap-2.5 text-body" :class="$attrs.class">
        <span class="relative mt-0.5 inline-flex size-4 shrink-0">
            <input
                v-model="model"
                v-bind="{ ...$attrs, class: undefined }"
                :value="value"
                type="checkbox"
                class="peer size-4 cursor-pointer appearance-none rounded-[5px] border border-line-strong bg-surface transition-colors checked:border-accent checked:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-50"
            />
            <Check class="pointer-events-none absolute inset-0 m-auto size-3 text-accent-ink opacity-0 peer-checked:opacity-100" stroke-width="3" aria-hidden="true" />
        </span>
        <span v-if="label || $slots.default" class="leading-snug">
            <span class="text-ink"><slot>{{ label }}</slot></span>
            <span v-if="description" class="mt-0.5 block text-small text-ink-3">{{ description }}</span>
        </span>
    </label>
</template>
