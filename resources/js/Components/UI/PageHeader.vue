<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    documentTitle: { type: String, default: null },
});
</script>

<template>
    <Head :title="documentTitle ?? title" />
    <header class="mb-6 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <slot name="eyebrow" />
            <h1 class="text-h2 text-ink sm:text-[1.75rem] sm:leading-tight sm:tracking-[-0.03em]">{{ title }}</h1>
            <p v-if="description" class="mt-1.5 max-w-2xl text-body text-ink-3">{{ description }}</p>
            <slot name="meta" />
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
