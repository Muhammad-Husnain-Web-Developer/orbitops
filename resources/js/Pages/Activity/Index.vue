<script setup>
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { Activity, ArrowUp, FolderKanban, SearchX, User } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useRealtime } from '@/composables/useRealtime';

const props = defineProps({
    activities: { type: Object, required: true },
    filters: { type: Object, required: true },
    types: { type: Array, required: true },
    people: { type: Array, required: true },
    projects: { type: Array, required: true },
});

const page = usePage();
const { can } = usePermissions();
const filters = reactive({ ...props.filters });
const loading = ref(false);
const fresh = ref(0);

const typeOptions = computed(() => props.types);
const peopleOptions = computed(() => props.people.map((person) => ({ value: String(person.id), label: person.name })));
const projectOptions = computed(() => props.projects.map((project) => ({ value: String(project.id), label: project.name })));
const hasFilters = computed(() => Boolean(filters.type || filters.person || filters.project));

function reload() {
    const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => value));

    // Filters start the timeline over, so drop the pages loaded so far.
    router.get(route('activity'), query, {
        only: ['activities', 'filters'],
        reset: ['activities'],
        preserveState: true,
        preserveScroll: false,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => {
            loading.value = false;
            fresh.value = 0;
        },
    });
}

watch(filters, reload);

function clear() {
    Object.assign(filters, { type: '', person: '', project: '' });
}

// New events from teammates: offer to load them rather than shifting the list under the reader.
const onRecorded = ({ activity }) => {
    if (activity.causer?.id !== page.props.auth.user.id) fresh.value++;
};
useRealtime(() => `workspace.${page.props.workspace.id}`, { '.activity.recorded': onRecorded });
useRealtime(() => (can('invoices.view') ? `workspace.${page.props.workspace.id}.invoices` : null), { '.activity.recorded': onRecorded });
useRealtime(() => (can('expenses.view') ? `workspace.${page.props.workspace.id}.expenses` : null), { '.activity.recorded': onRecorded });
</script>

<template>
    <PageHeader title="Activity" description="Everything that happened across the workspace, newest first." />

    <div class="mb-5 grid grid-cols-1 gap-2 sm:flex sm:flex-wrap">
        <Select v-model="filters.type" :options="typeOptions" placeholder="All activity" :icon="Activity" aria-label="Filter by type" class="sm:w-56" />
        <Select v-model="filters.person" :options="peopleOptions" placeholder="Everyone" :icon="User" aria-label="Filter by person" class="sm:w-48" />
        <Select v-model="filters.project" :options="projectOptions" placeholder="All projects" :icon="FolderKanban" aria-label="Filter by project" class="sm:w-52" />
        <Button v-if="hasFilters" variant="ghost" @click="clear">Clear</Button>
    </div>

    <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" leave-active-class="duration-150" leave-to-class="opacity-0">
        <div v-if="fresh" class="sticky top-16 z-20 mb-4 flex justify-center">
            <Button size="sm" :icon="ArrowUp" class="shadow-raised" @click="reload">{{ fresh }} new {{ fresh === 1 ? 'update' : 'updates' }}</Button>
        </div>
    </Transition>

    <Card class="transition-opacity" :class="loading ? 'opacity-60' : ''" :aria-busy="loading">
        <InfiniteScroll v-if="activities.data.length" data="activities" only-next :buffer="400">
            <ActivityFeed :items="activities.data" grouped />
            <template #loading>
                <div class="mt-6 space-y-5" aria-label="Loading more activity">
                    <div v-for="n in 3" :key="n" class="flex gap-3"><Skeleton class="size-8 rounded-full" /><div class="flex-1 space-y-2"><Skeleton class="h-3.5 w-2/3" /><Skeleton class="h-3 w-24" /></div></div>
                </div>
            </template>
        </InfiniteScroll>
        <EmptyState v-else-if="hasFilters" :icon="SearchX" title="No activity matches" description="Try another type, person or project." compact>
            <Button variant="secondary" @click="clear">Clear filters</Button>
        </EmptyState>
        <EmptyState v-else :icon="Activity" title="Nothing has happened yet" description="Create a project, log time or send an invoice and it shows up here." compact />
        <p v-if="activities.data.length && !activities.meta?.next_page_url && activities.meta?.current_page === activities.meta?.last_page" class="mt-6 border-t border-line pt-4 text-center text-caption text-ink-3">You've reached the beginning of the timeline.</p>
    </Card>
</template>
