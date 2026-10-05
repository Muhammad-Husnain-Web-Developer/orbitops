<script setup>
import { computed } from 'vue';
import { monotonePath } from './chart';

const props = defineProps({
    values: { type: Array, required: true },
    width: { type: Number, default: 96 },
    height: { type: Number, default: 28 },
    color: { type: String, default: 'var(--accent)' },
});

const points = computed(() => {
    const max = Math.max(...props.values, 1);
    const min = Math.min(...props.values, 0);
    const span = max - min || 1;
    const step = props.values.length > 1 ? (props.width - 4) / (props.values.length - 1) : 0;

    return props.values.map((value, index) => [2 + index * step, 2 + (props.height - 4) * (1 - (value - min) / span)]);
});

const path = computed(() => monotonePath(points.value));
</script>

<template>
    <svg :width="width" :height="height" :viewBox="`0 0 ${width} ${height}`" aria-hidden="true" class="overflow-visible">
        <path :d="path" fill="none" stroke="var(--ink-3)" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <circle v-if="points.length" :cx="points.at(-1)[0]" :cy="points.at(-1)[1]" r="2.5" :fill="color" stroke="var(--surface)" stroke-width="1.5" />
    </svg>
</template>
