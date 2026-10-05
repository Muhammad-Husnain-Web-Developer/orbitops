<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    tone: { type: String, default: 'accent' },
    size: { type: String, default: 'md' },
    label: { type: String, default: 'Progress' },
});

const tones = { accent: 'bg-accent', success: 'bg-success', warning: 'bg-warning', danger: 'bg-danger', neutral: 'bg-ink-3' };
const mounted = ref(false);
const clamped = computed(() => Math.min(100, Math.max(0, props.value)));

onMounted(() => requestAnimationFrame(() => (mounted.value = true)));
</script>

<template>
    <div
        role="progressbar"
        :aria-label="label"
        :aria-valuenow="Math.round(clamped)"
        aria-valuemin="0"
        aria-valuemax="100"
        class="w-full overflow-hidden rounded-full bg-subtle"
        :class="size === 'sm' ? 'h-1' : size === 'lg' ? 'h-2.5' : 'h-1.5'"
    >
        <div class="h-full rounded-full transition-[width] duration-700 ease-[var(--ease-out-expo)]" :class="tones[tone] ?? tones.accent" :style="{ width: `${mounted ? clamped : 0}%` }" />
    </div>
</template>
