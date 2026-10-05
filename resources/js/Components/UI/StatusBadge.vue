<script setup>
import { computed } from 'vue';
import { useEnums } from '@/composables/useEnums';
import Badge from './Badge.vue';

const props = defineProps({
    group: { type: String, required: true },
    value: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const { option } = useEnums();
const resolved = computed(() => option(props.group, props.value));
</script>

<template>
    <Badge :tone="resolved.tone" :size="size" dot>{{ resolved.label }}</Badge>
</template>
