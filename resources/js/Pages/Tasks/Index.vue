<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { CheckSquare, Columns3, List, Plus, SearchX } from '@lucide/vue';
import { computed } from 'vue';
import KanbanBoard from '@/Components/Tasks/KanbanBoard.vue';
import TaskDrawer from '@/Components/Tasks/TaskDrawer.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import Select from '@/Components/UI/Select.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Table from '@/Components/UI/Table.vue';
import { useEnums } from '@/composables/useEnums';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { useRealtime } from '@/composables/useRealtime';
import { useTaskDrawer } from '@/composables/useTaskDrawer';
import { swatch } from '@/lib/colors';
import { daysUntil, formatDate } from '@/lib/format';

const props = defineProps({
    tasks: { type: [Array, Object], required: true },
    filters: { type: Object, required: true },
    projects: { type: Array, required: true },
    members: { type: Array, required: true },
    activeTask: { type: Object, default: null },
});

const page = usePage();
const { can } = usePermissions();
const { options } = useEnums();
const { filters, loading, reset, sortBy } = useFilters(props.filters, { route: 'tasks.index', only: ['tasks', 'filters'] });
const drawer = useTaskDrawer(() => props.activeTask);

const rows = computed(() => (Array.isArray(props.tasks) ? props.tasks : props.tasks.data));
const hasFilters = computed(() => Boolean(filters.search || filters.project || filters.assignee || filters.priority || filters.status));

const projectOptions = computed(() => props.projects.map((project) => ({ value: String(project.id), label: project.name })));
const assigneeOptions = computed(() => [{ value: 'me', label: 'Assigned to me' }, { value: 'unassigned', label: 'Unassigned' }, ...props.members.map((member) => ({ value: String(member.id), label: member.name }))]);

const columns = [
    { key: 'title', label: 'Task', sortable: true },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority', sortable: true, hide: 'md' },
    { key: 'assignee', label: 'Assignee', hide: 'lg' },
    { key: 'due_date', label: 'Due', sortable: true, align: 'right' },
];

// Refresh the board when a teammate moves a card.
useRealtime(() => `workspace.${page.props.workspace.id}`, {
    '.task.moved': (event) => event.actor_id !== page.props.auth.user.id && router.reload({ only: ['tasks'] }),
});

function create(status = 'todo') {
    openQuickCreate('task', { status, project_id: filters.project ? Number(filters.project) : '' });
}
</script>

<template>
    <PageHeader title="Tasks" description="Every task across your projects. Drag cards to update their status.">
        <template #actions>
            <SegmentedControl
                v-model="filters.view"
                label="Task view"
                :options="[
                    { value: 'board', label: 'Board', icon: Columns3 },
                    { value: 'list', label: 'List', icon: List },
                ]"
            />
            <Button v-if="can('tasks.manage')" :icon="Plus" @click="create()">New task</Button>
        </template>
    </PageHeader>

    <div class="mb-5 flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
        <SearchInput v-model="filters.search" placeholder="Search tasks…" label="Search tasks" class="sm:w-64" />
        <Select v-model="filters.project" :options="projectOptions" placeholder="All projects" size="sm" aria-label="Filter by project" class="sm:w-48" />
        <Select v-model="filters.assignee" :options="assigneeOptions" placeholder="Anyone" size="sm" aria-label="Filter by assignee" class="sm:w-44" />
        <Select v-model="filters.priority" :options="options('taskPriority')" placeholder="Any priority" size="sm" aria-label="Filter by priority" class="sm:w-36" />
        <Select v-if="filters.view === 'list'" v-model="filters.status" :options="options('taskStatus')" placeholder="Any status" size="sm" aria-label="Filter by status" class="sm:w-36" />
        <Button v-if="hasFilters" variant="ghost" size="sm" @click="reset({ view: filters.view, sort: filters.sort, direction: filters.direction })">Clear filters</Button>
    </div>

    <div :class="loading ? 'opacity-60 transition-opacity' : 'transition-opacity'">
        <template v-if="filters.view === 'board'">
            <Card v-if="!rows.length && hasFilters">
                <EmptyState :icon="SearchX" title="No tasks match your filters" description="Try another project, person or priority.">
                    <Button variant="secondary" @click="reset({ view: filters.view })">Clear filters</Button>
                </EmptyState>
            </Card>
            <Card v-else-if="!rows.length">
                <EmptyState :icon="CheckSquare" title="No tasks yet." description="Break your projects into tasks and move them across the board as work progresses.">
                    <Button v-if="can('tasks.manage')" :icon="Plus" @click="create()">Create Task</Button>
                </EmptyState>
            </Card>
            <KanbanBoard v-else :tasks="rows" :readonly="!can('tasks.manage')" @open="drawer.openTask($event.id)" @create="create" />
        </template>

        <Card v-else :padded="false">
            <Table :columns="columns" :rows="rows" :sort="{ key: filters.sort, direction: filters.direction }" clickable caption="Tasks" @sort="sortBy" @row-click="drawer.openTask($event.id)">
                <template #cell-title="{ row }">
                    <button type="button" class="flex min-w-0 items-center gap-2.5 text-left" @click="drawer.openTask(row.id)">
                        <span class="size-2 shrink-0 rounded-full" :class="swatch(row.project?.color)" />
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-ink" :class="row.status === 'done' ? 'line-through decoration-ink-3' : ''">{{ row.title }}</span>
                            <span class="block truncate text-caption text-ink-3"><span class="font-mono">{{ row.key }}</span> · {{ row.project?.name }}</span>
                        </span>
                    </button>
                </template>
                <template #cell-status="{ row }"><StatusBadge group="taskStatus" :value="row.status" /></template>
                <template #cell-priority="{ row }"><StatusBadge group="taskPriority" :value="row.priority" size="sm" /></template>
                <template #cell-assignee="{ row }">
                    <span v-if="row.assignee" class="flex items-center gap-2"><Avatar :user="row.assignee" size="sm" decorative /><span class="truncate">{{ row.assignee.name }}</span></span>
                    <span v-else class="text-ink-3">Unassigned</span>
                </template>
                <template #cell-due_date="{ row }">
                    <span v-if="row.due_date" class="tabular" :class="daysUntil(row.due_date) < 0 && row.status !== 'done' ? 'font-medium text-danger' : 'text-ink-2'">{{ formatDate(row.due_date, 'short') }}</span>
                    <span v-else class="text-ink-3">—</span>
                </template>
                <template #mobile="{ row }">
                    <button type="button" class="w-full text-left" @click="drawer.openTask(row.id)">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium text-ink">{{ row.title }}</p>
                            <StatusBadge group="taskStatus" :value="row.status" size="sm" />
                        </div>
                        <p class="mt-1 text-caption text-ink-3">{{ row.key }} · {{ row.project?.name }}<template v-if="row.due_date"> · Due {{ formatDate(row.due_date, 'short') }}</template></p>
                    </button>
                </template>
                <template #empty>
                    <EmptyState :icon="hasFilters ? SearchX : CheckSquare" :title="hasFilters ? 'No tasks match your filters' : 'No tasks yet.'" :description="hasFilters ? 'Try different filters.' : 'Create your first task to get started.'" />
                </template>
            </Table>
            <Pagination v-if="!Array.isArray(tasks) && rows.length" :meta="tasks.meta" :links="tasks.links" :only="['tasks']" />
        </Card>
    </div>

    <TaskDrawer v-model:open="drawer.open.value" :task="activeTask" :loading="drawer.loading.value" :members="members" />
</template>
