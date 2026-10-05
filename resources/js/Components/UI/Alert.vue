<script setup>
import { CircleAlert, CircleCheck, Info, TriangleAlert } from '@lucide/vue';

defineProps({
    tone: { type: String, default: 'info' },
    title: { type: String, default: null },
});

const styles = {
    success: ['border-success/25 bg-success/8 text-success', CircleCheck],
    danger: ['border-danger/25 bg-danger/8 text-danger', CircleAlert],
    warning: ['border-warning/30 bg-warning/8 text-warning', TriangleAlert],
    info: ['border-info/25 bg-info/8 text-info', Info],
};
</script>

<template>
    <div class="flex gap-3 rounded-xl border px-4 py-3" :class="styles[tone][0]" :role="tone === 'danger' ? 'alert' : 'status'">
        <component :is="styles[tone][1]" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <div class="min-w-0 text-small">
            <p v-if="title" class="font-semibold">{{ title }}</p>
            <div class="text-ink-2"><slot /></div>
        </div>
    </div>
</template>
