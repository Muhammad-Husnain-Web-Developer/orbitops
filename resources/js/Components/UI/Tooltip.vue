<script setup>
import { useId } from 'vue';

defineProps({
    text: { type: String, required: true },
    side: { type: String, default: 'top' },
});

const id = useId();
</script>

<template>
    <span class="group/tooltip relative inline-flex" :aria-describedby="id">
        <slot />
        <span
            :id="id"
            role="tooltip"
            class="pointer-events-none absolute left-1/2 z-50 -translate-x-1/2 rounded-md bg-ink px-2 py-1 text-caption font-medium whitespace-nowrap text-canvas opacity-0 shadow-raised transition-opacity duration-150 group-hover/tooltip:opacity-100 group-focus-within/tooltip:opacity-100 group-hover/tooltip:delay-300"
            :class="side === 'bottom' ? 'top-full mt-2' : 'bottom-full mb-2'"
            >{{ text }}</span
        >
    </span>
</template>
