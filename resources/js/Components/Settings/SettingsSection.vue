<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    danger: { type: Boolean, default: false },
});
</script>

<template>
    <section class="rounded-xl border bg-surface shadow-card" :class="danger ? 'border-danger/30' : 'border-line'" :aria-label="title">
        <header class="border-b px-5 py-4 sm:px-6" :class="danger ? 'border-danger/20' : 'border-line'">
            <h2 class="text-h3" :class="danger ? 'text-danger' : 'text-ink'">{{ title }}</h2>
            <p v-if="description" class="mt-0.5 text-small text-ink-3">{{ description }}</p>
        </header>
        <div class="px-5 py-5 sm:px-6">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-3 rounded-b-xl border-t px-5 py-3 sm:px-6" :class="danger ? 'border-danger/20 bg-danger/5' : 'border-line bg-canvas/40'">
            <slot name="footer" />
        </footer>
    </section>
</template>
