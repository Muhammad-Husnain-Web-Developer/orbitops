<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    padded: { type: Boolean, default: true },
    as: { type: String, default: 'section' },
});
</script>

<template>
    <component :is="as" class="rounded-xl border border-line bg-surface shadow-card">
        <header v-if="title || $slots.header || $slots.actions" class="flex items-start justify-between gap-4 px-5 pt-4" :class="padded ? '' : 'pb-4'">
            <slot name="header">
                <div class="min-w-0">
                    <h2 class="text-h3 text-ink">{{ title }}</h2>
                    <p v-if="description" class="mt-0.5 text-small text-ink-3">{{ description }}</p>
                </div>
            </slot>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </header>
        <div :class="padded ? 'p-5' : ''">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="border-t border-line px-5 py-3">
            <slot name="footer" />
        </footer>
    </component>
</template>
