<script setup>
import { computed } from 'vue';
import Avatar from './Avatar.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    max: { type: Number, default: 4 },
    size: { type: String, default: 'sm' },
});

const visible = computed(() => props.users.slice(0, props.max));
const overflow = computed(() => Math.max(0, props.users.length - props.max));
</script>

<template>
    <div class="flex items-center -space-x-1.5">
        <Avatar v-for="user in visible" :key="user.id" :user="user" :size="size" class="ring-2 ring-surface" />
        <span
            v-if="overflow"
            class="inline-flex items-center justify-center rounded-full bg-subtle font-medium text-ink-2 ring-2 ring-surface"
            :class="size === 'xs' ? 'size-5 text-[0.5625rem]' : size === 'md' ? 'size-8 text-caption' : 'size-6 text-[0.625rem]'"
            >+{{ overflow }}</span
        >
    </div>
</template>
