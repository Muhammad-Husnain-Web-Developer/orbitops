<script setup>
import { RotateCw, TriangleAlert } from '@lucide/vue';
import Button from './Button.vue';

defineProps({
    title: { type: String, default: 'Something went wrong.' },
    description: { type: String, default: "We couldn't load this. Check your connection and try again." },
    retrying: { type: Boolean, default: false },
});

defineEmits(['retry']);
</script>

<template>
    <div class="flex flex-col items-center px-6 py-12 text-center" role="alert">
        <div class="mb-4 flex size-11 items-center justify-center rounded-xl border border-danger/20 bg-danger/10">
            <TriangleAlert class="size-5 text-danger" aria-hidden="true" />
        </div>
        <h3 class="text-h3 text-ink">{{ title }}</h3>
        <p class="mt-1 max-w-sm text-body text-ink-3">{{ description }}</p>
        <Button class="mt-5" variant="secondary" :icon="RotateCw" :loading="retrying" @click="$emit('retry')">Try again</Button>
    </div>
</template>
