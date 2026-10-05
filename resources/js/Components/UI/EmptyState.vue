<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    description: { type: String, default: null },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex flex-col items-center text-center" :class="compact ? 'px-6 py-10' : 'px-6 py-16 sm:py-20'">
        <div class="relative mb-5" :class="compact ? 'size-16' : 'size-24'" aria-hidden="true">
            <!-- Orbit illustration: rings, a satellite and the module icon at the core. -->
            <svg class="absolute inset-0 size-full text-line-strong" viewBox="0 0 96 96" fill="none">
                <circle cx="48" cy="48" r="46" stroke="currentColor" stroke-dasharray="2 5" />
                <circle cx="48" cy="48" r="32" stroke="currentColor" opacity="0.7" />
                <circle cx="86" cy="30" r="3" class="fill-accent" />
                <circle cx="14" cy="66" r="2" fill="currentColor" />
            </svg>
            <div class="absolute inset-0 m-auto flex items-center justify-center rounded-2xl border border-line bg-elevated shadow-raised" :class="compact ? 'size-9' : 'size-12'">
                <component :is="icon" class="text-accent-text" :class="compact ? 'size-4' : 'size-5'" />
            </div>
        </div>
        <h3 class="text-h3 text-ink">{{ title }}</h3>
        <p v-if="description" class="mt-1.5 max-w-sm text-body text-ink-3">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
