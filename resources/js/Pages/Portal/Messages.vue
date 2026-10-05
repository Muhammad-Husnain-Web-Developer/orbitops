<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, MessageSquare } from '@lucide/vue';
import { computed } from 'vue';
import MessageThread from '@/Components/Portal/MessageThread.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { swatch } from '@/lib/colors';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    threads: { type: Array, required: true },
    selected: { type: Number, default: null },
    messages: { type: Array, default: () => [] },
});

const page = usePage();
const current = computed(() => props.threads.find((thread) => thread.id === props.selected) ?? null);
const chosen = computed(() => new URL(page.url, 'http://x').searchParams.has('project'));
</script>

<template>
    <Head title="Messages" />

    <header class="mb-6">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Messages</h1>
        <p class="mt-1 text-body text-ink-3">One conversation per project, straight to the {{ page.props.workspace.name }} team.</p>
    </header>

    <Card v-if="!threads.length"><EmptyState :icon="MessageSquare" title="No projects yet" description="Once a project starts, you can message the team here." /></Card>

    <div v-else class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
        <nav aria-label="Project conversations" :class="chosen ? 'max-lg:hidden' : ''">
            <ul class="space-y-1">
                <li v-for="thread in threads" :key="thread.id">
                    <Link
                        :href="route('portal.messages', { project: thread.id })"
                        preserve-scroll
                        class="block rounded-xl border px-4 py-3 transition-colors"
                        :class="thread.id === selected ? 'border-accent/40 bg-accent/8' : 'border-transparent hover:bg-hover'"
                        :aria-current="thread.id === selected ? 'true' : undefined"
                    >
                        <p class="flex items-center gap-2 text-body font-medium text-ink"><span class="size-2 shrink-0 rounded-full" :class="swatch(thread.color)" /><span class="truncate">{{ thread.name }}</span></p>
                        <p v-if="thread.last" class="mt-0.5 truncate text-small text-ink-3">{{ thread.last.is_mine ? 'You' : thread.last.author?.name?.split(' ')[0] }}: {{ thread.last.body }}</p>
                        <p v-else class="mt-0.5 text-small text-ink-3">No messages yet</p>
                        <p v-if="thread.last" class="mt-0.5 text-caption text-ink-3">{{ formatRelative(thread.last.created_at) }}</p>
                    </Link>
                </li>
            </ul>
        </nav>

        <Card v-if="current" :class="chosen ? '' : 'max-lg:hidden'">
            <template #header>
                <div class="flex items-center gap-3">
                    <Link :href="route('portal.messages')" class="-ml-1 rounded-md p-1 text-ink-3 hover:bg-hover hover:text-ink lg:hidden" aria-label="All conversations"><ArrowLeft class="size-4" /></Link>
                    <div>
                        <h2 class="text-h3 text-ink">{{ current.name }}</h2>
                        <Link :href="route('portal.projects.show', current.id)" class="text-small text-accent-text hover:underline">View project</Link>
                    </div>
                </div>
            </template>
            <MessageThread :messages="messages" :project-id="current.id" :only="['messages', 'threads']" :team-name="page.props.workspace.name" />
        </Card>
    </div>
</template>
