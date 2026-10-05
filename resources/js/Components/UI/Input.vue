<script setup>
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({
    invalid: { type: Boolean, default: false },
    icon: { type: [Object, Function], default: null },
    prefix: { type: String, default: null },
    suffix: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const input = ref(null);

defineExpose({ focus: () => input.value?.focus(), el: input });
</script>

<template>
    <div class="relative flex items-center" :class="$attrs.class">
        <component :is="icon" v-if="icon" class="pointer-events-none absolute left-3 size-4 text-ink-3" aria-hidden="true" />
        <span v-else-if="prefix" class="pointer-events-none absolute left-3 text-body text-ink-3">{{ prefix }}</span>
        <input
            ref="input"
            v-model="model"
            v-bind="{ ...$attrs, class: undefined }"
            :aria-invalid="invalid || undefined"
            class="w-full rounded-lg border border-line bg-surface text-ink shadow-card transition-[border-color,box-shadow] duration-150 placeholder:text-ink-3 hover:border-line-strong focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/20 disabled:cursor-not-allowed disabled:opacity-60 aria-invalid:border-danger aria-invalid:focus:ring-danger/20"
            :class="[
                size === 'sm' ? 'h-8 text-small' : size === 'lg' ? 'h-11 text-[0.9375rem]' : 'h-9 text-body',
                icon || prefix ? 'pl-9' : 'pl-3',
                suffix ? 'pr-14' : 'pr-3',
            ]"
        />
        <span v-if="suffix" class="pointer-events-none absolute right-3 text-small text-ink-3">{{ suffix }}</span>
    </div>
</template>
