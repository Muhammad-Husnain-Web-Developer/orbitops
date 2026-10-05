<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, Settings } from '@lucide/vue';
import { computed } from 'vue';
import NotificationItem from '@/Components/Layout/NotificationItem.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { useRealtime } from '@/composables/useRealtime';
import { api } from '@/lib/api';
import { groupByDay } from '@/lib/notifications';

const props = defineProps({
    items: { type: Object, required: true },
    filter: { type: String, default: 'all' },
});

const page = usePage();
const unread = computed(() => page.props.notifications?.unread ?? 0);
const groups = computed(() => groupByDay(props.items.data));

const tabs = computed(() => [
    { key: 'all', label: 'All', href: route('notifications.index') },
    { key: 'unread', label: 'Unread', count: unread.value || null, href: route('notifications.index', { filter: 'unread' }) },
]);

function open(notification) {
    if (!notification.read_at) {
        notification.read_at = new Date().toISOString();
        api.post(route('notifications.read', notification.id)).catch(() => {});
    }

    if (notification.url) router.visit(notification.url);
}

function markAll() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true, only: ['items', 'notifications'] });
}

// New notifications for this workspace refresh the list in place.
useRealtime(() => `App.Models.User.${page.props.auth.user.id}`, {
    notification: (notification) => {
        if (notification.workspace_id && notification.workspace_id !== page.props.workspace?.id) return;
        router.reload({ only: ['items', 'notifications'] });
    },
});
</script>

<template>
    <PageHeader title="Notifications" description="Mentions, assignments, client messages and payments that need your attention.">
        <template #actions>
            <Button variant="ghost" :icon="Settings" :href="route('settings.notifications')">Preferences</Button>
            <Button variant="secondary" :icon="CheckCheck" :disabled="!unread" @click="markAll">Mark all as read</Button>
        </template>
    </PageHeader>

    <Card :padded="false" class="mx-auto max-w-3xl">
        <div class="px-4 pt-2">
            <Tabs :model-value="filter" :tabs="tabs" label="Filter notifications" />
        </div>

        <div v-if="items.data.length" class="p-2 sm:p-3">
            <section v-for="group in groups" :key="group.label" :aria-label="group.label" class="mb-2 last:mb-0">
                <h2 class="px-3 pt-3 pb-1 text-caption font-medium text-ink-3">{{ group.label }}</h2>
                <ul>
                    <li v-for="notification in group.items" :key="notification.id"><NotificationItem :notification="notification" @open="open" /></li>
                </ul>
            </section>
        </div>
        <EmptyState
            v-else
            :icon="Bell"
            :title="filter === 'unread' ? 'You’re all caught up' : 'No notifications yet'"
            :description="filter === 'unread' ? 'New mentions, assignments and payments will show up here.' : 'When teammates mention you or clients reply, you will hear about it here.'"
            compact
        />

        <Pagination v-if="items.meta.last_page > 1" :meta="items.meta" :links="items.links" class="border-t border-line" />
    </Card>
</template>
