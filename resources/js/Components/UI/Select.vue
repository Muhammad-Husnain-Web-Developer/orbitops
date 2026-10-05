<script setup>
import { ChevronDown } from '@lucide/vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, Boolean, null], default: '' });

defineProps({
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: null },
    invalid: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="relative" :class="$attrs.class">
        <component :is="icon" v-if="icon" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-3" aria-hidden="true" />
        <select
            v-model="model"
            v-bind="{ ...$attrs, class: undefined }"
            :aria-invalid="invalid || undefined"
            class="w-full cursor-pointer appearance-none truncate rounded-lg border border-line bg-surface pr-9 text-ink shadow-card transition-[border-color,box-shadow] duration-150 hover:border-line-strong focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/20 disabled:opacity-60 aria-invalid:border-danger"
            :class="[size === 'sm' ? 'h-8 text-small' : 'h-9 text-body', icon ? 'pl-9' : 'pl-3']"
        >
            <option v-if="placeholder !== null" value="">{{ placeholder }}</option>
            <slot>
                <option v-for="option in options" :key="String(option.value)" :value="option.value" :disabled="option.disabled">{{ option.label }}</option>
            </slot>
        </select>
        <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-3" aria-hidden="true" />
    </div>
</template>
