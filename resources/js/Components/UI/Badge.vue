<script setup>
defineProps({
    tone: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const tones = {
    neutral: 'bg-subtle text-ink-2 ring-line',
    accent: 'bg-accent/10 text-accent-text ring-accent/20',
    success: 'bg-success/10 text-success ring-success/20',
    warning: 'bg-warning/10 text-warning ring-warning/25',
    danger: 'bg-danger/10 text-danger ring-danger/20',
    info: 'bg-info/10 text-info ring-info/20',
};

const dots = {
    neutral: 'bg-ink-3',
    accent: 'bg-accent',
    success: 'bg-success',
    warning: 'bg-warning',
    danger: 'bg-danger',
    info: 'bg-info',
};
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full font-medium whitespace-nowrap ring-1 ring-inset"
        :class="[tones[tone] ?? tones.neutral, size === 'sm' ? 'px-1.5 py-px text-[0.6875rem]' : 'px-2 py-0.5 text-caption']"
    >
        <span v-if="dot" class="size-1.5 rounded-full" :class="dots[tone] ?? dots.neutral" aria-hidden="true" />
        <slot />
    </span>
</template>
