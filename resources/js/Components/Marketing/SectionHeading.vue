<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    description: { type: String, default: null },
    align: { type: String, default: 'center' },
    headingId: { type: String, default: null },
});
</script>

<template>
    <div :class="align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl'">
        <p v-if="eyebrow" data-reveal class="mb-4 inline-flex items-center gap-2 text-eyebrow text-accent-text uppercase">
            <span class="h-px w-6 bg-accent/60" aria-hidden="true" />
            {{ eyebrow }}
        </p>
        <h2 :id="headingId" data-reveal class="text-h1 text-balance text-ink">{{ title }}</h2>
        <p v-if="description" data-reveal class="mt-5 text-lead text-pretty text-ink-3">{{ description }}</p>
    </div>
</template>
