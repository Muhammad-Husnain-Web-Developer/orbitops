<script setup>
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, CircleDashed, FolderKanban, ListChecks, Timer } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Select from '@/Components/UI/Select.vue';
import { swatch } from '@/lib/colors';
import { formatDate, formatRelative } from '@/lib/format';

const props = defineProps({
    tasks: { type: Array, required: true },
    projects: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const project = ref(props.filters.project);
watch(project, (value) => router.get(route('portal.tasks'), value ? { project: value } : {}, { preserveState: true, preserveScroll: true, replace: true }));

const projectOptions = computed(() => props.projects.map((item) => ({ value: String(item.id), label: item.name })));
const groups = computed(() => [
    { key: 'active', label: 'In progress', icon: Timer, items: props.tasks.filter((task) => ['in_progress', 'review'].includes(task.status)) },
    { key: 'next', label: 'Up next', icon: CircleDashed, items: props.tasks.filter((task) => ['todo', 'backlog'].includes(task.status)) },
    { key: 'done', label: 'Done', icon: CheckCircle2, items: props.tasks.filter((task) => task.status === 'done').reverse() },
]);
</script>

<template>
    <Head title="Tasks" />

    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h2 text-ink sm:text-[1.75rem]">Tasks</h1>
            <p class="mt-1 text-body text-ink-3">The work the team is sharing with you, across all projects.</p>
        </div>
        <Select v-model="project" :options="projectOptions" placeholder="All projects" :icon="FolderKanban" aria-label="Filter by project" class="sm:w-56" />
    </header>

    <Card v-if="!tasks.length"><EmptyState :icon="ListChecks" title="No shared tasks" description="Tasks the team shares with you will show up here." /></Card>

    <div v-else class="space-y-6">
        <Card v-for="group in groups.filter((item) => item.items.length)" :key="group.key" :padded="false">
            <template #header>
                <h2 class="flex items-center gap-2 text-h3 text-ink"><component :is="group.icon" class="size-4 text-ink-3" />{{ group.label }} <span class="text-small font-normal text-ink-3 tabular">{{ group.items.length }}</span></h2>
            </template>
            <ul class="divide-y divide-line border-t border-line">
                <li v-for="task in group.items" :key="task.id" class="flex items-center gap-3 px-5 py-3">
                    <span class="size-2 shrink-0 rounded-full" :class="swatch(task.project?.color)" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-body" :class="task.status === 'done' ? 'text-ink-3 line-through' : 'text-ink'">{{ task.title }}</p>
                        <p class="truncate text-caption text-ink-3">{{ task.project?.name }} · <span class="font-mono">{{ task.key }}</span><template v-if="task.status === 'review'"> · In review</template></p>
                    </div>
                    <span class="shrink-0 text-small text-ink-3">{{ task.completed_at ? `Done ${formatRelative(task.completed_at)}` : task.due_date ? `Due ${formatDate(task.due_date, 'short')}` : '' }}</span>
                </li>
            </ul>
        </Card>
    </div>
</template>
