<script setup>
defineProps({
    wordmark: { type: Boolean, default: true },
    size: { type: Number, default: 26 },
});
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <svg :width="size" :height="size" viewBox="0 0 32 32" fill="none" aria-hidden="true" class="shrink-0">
            <defs>
                <linearGradient id="orbitops-core" x1="6" y1="4" x2="26" y2="28" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#a78bfa" />
                    <stop offset="1" stop-color="#6d5dfc" />
                </linearGradient>
            </defs>
            <rect width="32" height="32" rx="9" fill="url(#orbitops-core)" />
            <ellipse cx="16" cy="16" rx="11" ry="5.2" transform="rotate(-28 16 16)" stroke="white" stroke-opacity="0.85" stroke-width="1.6" />
            <circle cx="16" cy="16" r="3.6" fill="white" />
            <circle cx="24.6" cy="11.4" r="1.9" fill="#22d3ee" />
        </svg>
        <span v-if="wordmark" class="text-[1.0625rem] font-semibold tracking-[-0.03em] text-ink">OrbitOps</span>
        <span v-else class="sr-only">OrbitOps</span>
    </span>
</template>
