<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    size: { type: Number, default: 44 },
    stroke: { type: Number, default: 4 },
    tone: { type: String, default: 'accent' },
    label: { type: String, default: 'Progress' },
});

const tones = { accent: 'text-accent', success: 'text-success', warning: 'text-warning', danger: 'text-danger' };
const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const mounted = ref(false);
const offset = computed(() => circumference.value * (1 - (mounted.value ? Math.min(100, Math.max(0, props.value)) : 0) / 100));

onMounted(() => requestAnimationFrame(() => (mounted.value = true)));
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }" role="progressbar" :aria-label="label" :aria-valuenow="Math.round(value)" aria-valuemin="0" aria-valuemax="100">
        <svg :width="size" :height="size" class="-rotate-90" aria-hidden="true">
            <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" stroke="var(--subtle)" :stroke-width="stroke" />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke="currentColor"
                :stroke-width="stroke"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-[stroke-dashoffset] duration-1000 ease-[var(--ease-out-expo)]"
                :class="tones[tone] ?? tones.accent"
            />
        </svg>
        <span class="absolute text-caption font-semibold text-ink tabular"><slot>{{ Math.round(value) }}%</slot></span>
    </div>
</template>
