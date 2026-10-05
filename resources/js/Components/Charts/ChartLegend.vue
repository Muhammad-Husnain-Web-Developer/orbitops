<script setup>
defineProps({
    series: { type: Array, required: true },
    shape: { type: String, default: 'line' },
});
</script>

<template>
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1.5" aria-label="Legend">
        <li v-for="item in series" :key="item.key" class="inline-flex items-center gap-1.5 text-caption text-ink-2">
            <span v-if="shape === 'line'" class="h-0.5 w-3 rounded-full" :style="{ background: item.color }" aria-hidden="true" />
            <span v-else class="size-2.5 rounded-[3px]" :style="{ background: item.color }" aria-hidden="true" />
            {{ item.name }}
        </li>
    </ul>
</template>
