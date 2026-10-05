<script setup>
import { computed } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { formatRelative } from '@/lib/format';
import { notificationMeta } from '@/lib/notifications';

const props = defineProps({
    notification: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

defineEmits(['open']);

const meta = computed(() => notificationMeta(props.notification.type));
</script>

<template>
    <button type="button" class="group flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-hover focus-visible:bg-hover" @click="$emit('open', notification)">
        <span class="relative mt-0.5 shrink-0">
            <Avatar v-if="notification.actor" :user="notification.actor" size="md" decorative />
            <span v-else class="flex size-8 items-center justify-center rounded-full" :class="meta.tone"><component :is="meta.icon" class="size-4" aria-hidden="true" /></span>
            <span v-if="notification.actor" class="absolute -right-1 -bottom-1 flex size-4.5 items-center justify-center rounded-full ring-2 ring-elevated" :class="meta.tone"><component :is="meta.icon" class="size-2.5" aria-hidden="true" /></span>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-body leading-snug" :class="notification.read_at ? 'text-ink-2' : 'font-medium text-ink'">{{ notification.title }}</span>
            <span v-if="notification.body" class="mt-0.5 block text-small text-ink-3" :class="compact ? 'truncate' : 'line-clamp-2'">{{ notification.body }}</span>
            <span class="mt-1 block text-caption text-ink-3">{{ formatRelative(notification.created_at) }}</span>
        </span>
        <span v-if="!notification.read_at" class="mt-2 size-2 shrink-0 rounded-full bg-accent" aria-label="Unread" />
    </button>
</template>
