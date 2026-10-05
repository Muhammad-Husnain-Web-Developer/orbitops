<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, BellOff, CheckCheck } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ErrorState from '@/Components/UI/ErrorState.vue';
import Popover from '@/Components/UI/Popover.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import { useRealtime } from '@/composables/useRealtime';
import { toast } from '@/composables/useToast';
import { api } from '@/lib/api';
import { groupByDay } from '@/lib/notifications';
import NotificationItem from './NotificationItem.vue';

const page = usePage();
const popover = ref(null);
const items = ref([]);
const unread = ref(page.props.notifications?.unread ?? 0);
const state = ref('idle');
const tab = ref('all');

watch(() => page.props.notifications?.unread, (value) => (unread.value = value ?? 0));

const visible = computed(() => (tab.value === 'unread' ? items.value.filter((item) => !item.read_at) : items.value));
const groups = computed(() => groupByDay(visible.value));

async function load() {
    state.value = items.value.length ? 'refreshing' : 'loading';

    try {
        const response = await api.get(route('notifications.feed'));
        items.value = response.data;
        unread.value = response.unread;
        state.value = 'ready';
    } catch {
        state.value = 'error';
    }
}

async function open(notification) {
    if (!notification.read_at) {
        notification.read_at = new Date().toISOString();
        unread.value = Math.max(0, unread.value - 1);
        api.post(route('notifications.read', notification.id)).catch(() => {});
    }

    popover.value?.close(false);

    if (notification.url) {
        router.visit(notification.url);
    }
}

async function markAll() {
    items.value.forEach((item) => (item.read_at ??= new Date().toISOString()));
    unread.value = 0;
    await api.post(route('notifications.read-all')).catch(() => load());
}

// Realtime: new notifications bump the badge, join the open list and show a toast.
useRealtime(() => `App.Models.User.${page.props.auth.user.id}`, {
    notification: (notification) => {
        if (notification.workspace_id && notification.workspace_id !== page.props.workspace?.id) return;

        unread.value += 1;

        if (state.value === 'ready') {
            items.value.unshift({ ...notification, read_at: null });
        }

        toast.info(notification.title, {
            description: notification.body,
            action: notification.url ? { label: 'View', onClick: () => router.visit(notification.url) } : null,
        });
    },
});
</script>

<template>
    <Popover ref="popover" label="Notifications" width="w-[24rem]" @open="load">
        <template #trigger="{ attrs }">
            <button
                type="button"
                v-bind="attrs"
                class="relative inline-flex size-9 items-center justify-center rounded-lg text-ink-2 transition-colors hover:bg-hover hover:text-ink"
                :aria-label="unread ? `Notifications, ${unread} unread` : 'Notifications'"
            >
                <Bell class="size-[18px]" />
                <span v-if="unread" class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[0.625rem] font-bold text-accent-ink ring-2 ring-canvas tabular">{{ unread > 9 ? '9+' : unread }}</span>
            </button>
        </template>

        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <h2 class="text-h3 text-ink">Notifications</h2>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-small font-medium text-ink-3 transition-colors hover:bg-hover hover:text-ink disabled:opacity-40" :disabled="!unread" @click="markAll">
                <CheckCheck class="size-3.5" />Mark all read
            </button>
        </div>
        <div class="flex gap-1 border-b border-line px-3 pt-1" role="tablist" aria-label="Filter notifications">
            <button
                v-for="option in [{ key: 'all', label: 'All' }, { key: 'unread', label: `Unread${unread ? ` (${unread})` : ''}` }]"
                :key="option.key"
                type="button"
                role="tab"
                :aria-selected="tab === option.key"
                class="-mb-px border-b-2 px-2.5 py-2 text-small font-medium transition-colors"
                :class="tab === option.key ? 'border-accent text-ink' : 'border-transparent text-ink-3 hover:text-ink'"
                @click="tab = option.key"
            >
                {{ option.label }}
            </button>
        </div>

        <div class="max-h-[min(28rem,65vh)] overflow-y-auto p-1.5" :class="state === 'refreshing' ? 'opacity-70' : ''">
            <div v-if="state === 'loading'" class="space-y-1 p-2" aria-busy="true">
                <div v-for="n in 4" :key="n" class="flex gap-3 py-2">
                    <Skeleton class="size-8 rounded-full" />
                    <div class="flex-1 space-y-2"><Skeleton class="h-3.5 w-4/5" /><Skeleton class="h-3 w-1/3" /></div>
                </div>
            </div>
            <ErrorState v-else-if="state === 'error'" description="We couldn't load your notifications." @retry="load" />
            <div v-else-if="!visible.length" class="flex flex-col items-center px-6 py-12 text-center">
                <span class="flex size-11 items-center justify-center rounded-xl bg-subtle"><BellOff class="size-5 text-ink-3" /></span>
                <p class="mt-3 text-body font-medium text-ink">{{ tab === 'unread' ? "You're all caught up" : 'No notifications yet' }}</p>
                <p class="mt-1 text-small text-ink-3">Assignments, mentions and payments will show up here.</p>
            </div>
            <template v-else>
                <section v-for="group in groups" :key="group.label" class="pb-1">
                    <h3 class="px-3 pt-2 pb-1 text-eyebrow text-ink-3 uppercase">{{ group.label }}</h3>
                    <NotificationItem v-for="item in group.items" :key="item.id" :notification="item" compact @open="open" />
                </section>
            </template>
        </div>
        <Link :href="route('notifications.index')" class="block border-t border-line px-4 py-2.5 text-center text-small font-medium text-accent-text hover:bg-hover" @click="popover?.close(false)">View all notifications</Link>
    </Popover>
</template>
