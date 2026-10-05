<script setup>
import { Link, router } from '@inertiajs/vue3';
import { FolderKanban, LayoutGrid, List, Plus, SearchX } from '@lucide/vue';
import { computed } from 'vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import AvatarStack from '@/Components/UI/AvatarStack.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Table from '@/Components/UI/Table.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { useEnums } from '@/composables/useEnums';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { swatch } from '@/lib/colors';
import { daysUntil, formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    projects: { type: Object, required: true },
    filters: { type: Object, required: true },
    clients: { type: Array, required: true },
    counts: { type: Object, required: true },
});

const { can } = usePermissions();
const { options } = useEnums();
const { filters, loading, reset, sortBy } = useFilters(props.filters, { route: 'projects.index', only: ['projects', 'filters'] });

const openCount = computed(() => ['planning', 'active', 'on_hold'].reduce((sum, key) => sum + (props.counts[key] ?? 0), 0));
const statusTabs = computed(() => [
    { key: 'open', label: 'Open', count: openCount.value },
    ...options('projectStatus').map((status) => ({ key: status.value, label: status.label, count: props.counts[status.value] ?? 0 })),
]);

const clientOptions = computed(() => props.clients.map((client) => ({ value: String(client.id), label: client.name })));
const sortOptions = [
    { value: 'due_date', label: 'Due date' },
    { value: 'name', label: 'Name' },
    { value: 'created_at', label: 'Newest' },
    { value: 'budget', label: 'Budget' },
];
const hasFilters = computed(() => Boolean(filters.search || filters.client));

const columns = [
    { key: 'name', label: 'Project', sortable: true },
    { key: 'status', label: 'Status' },
    { key: 'progress', label: 'Progress', width: 'w-40', hide: 'md' },
    { key: 'budget', label: 'Budget', sortable: true, align: 'right', hide: 'lg' },
    { key: 'members', label: 'Team', hide: 'xl' },
    { key: 'due_date', label: 'Due', sortable: true, align: 'right' },
];
</script>

<template>
    <PageHeader title="Projects" description="Track progress, budgets and deadlines across every client engagement.">
        <template #actions>
            <SegmentedControl
                v-model="filters.view"
                label="Project view"
                :options="[
                    { value: 'grid', label: 'Grid', icon: LayoutGrid },
                    { value: 'list', label: 'List', icon: List },
                ]"
            />
            <Button v-if="can('projects.manage')" :icon="Plus" @click="openQuickCreate('project')">New project</Button>
        </template>
    </PageHeader>

    <Tabs v-model="filters.status" :tabs="statusTabs" label="Filter projects by status" />

    <div class="mt-4 mb-5 flex flex-col gap-2.5 sm:flex-row sm:items-center">
        <SearchInput v-model="filters.search" placeholder="Search by name or key…" label="Search projects" class="sm:w-72" />
        <Select v-model="filters.client" :options="clientOptions" placeholder="All clients" size="sm" aria-label="Filter by client" class="sm:w-52" />
        <Select v-model="filters.sort" :options="sortOptions" size="sm" aria-label="Sort projects" class="sm:ml-auto sm:w-40" />
    </div>

    <div :class="loading ? 'opacity-60 transition-opacity' : 'transition-opacity'">
        <template v-if="!projects.data.length">
            <Card>
                <EmptyState v-if="hasFilters" :icon="SearchX" title="No projects match your filters" description="Try another search or client.">
                    <Button variant="secondary" @click="reset({ status: filters.status, view: filters.view, sort: filters.sort, direction: filters.direction })">Clear filters</Button>
                </EmptyState>
                <EmptyState v-else :icon="FolderKanban" title="No projects yet." description="Create your first project and bring your team into the workflow.">
                    <Button v-if="can('projects.manage')" :icon="Plus" @click="openQuickCreate('project')">Create Project</Button>
                </EmptyState>
            </Card>
        </template>

        <div v-else-if="filters.view === 'grid'" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <ProjectCard v-for="project in projects.data" :key="project.id" :project="project" />
        </div>

        <Card v-else :padded="false">
            <Table :columns="columns" :rows="projects.data" :sort="{ key: filters.sort, direction: filters.direction }" clickable caption="Projects" @sort="sortBy" @row-click="(row) => router.visit(route('projects.show', row.id))">
                <template #cell-name="{ row }">
                    <Link :href="route('projects.show', row.id)" class="flex items-center gap-3">
                        <span class="size-2.5 shrink-0 rounded-full" :class="swatch(row.color)" />
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-ink">{{ row.name }}</span>
                            <span class="block truncate text-caption text-ink-3"><span class="font-mono">{{ row.code }}</span> · {{ row.client?.name ?? 'Internal' }}</span>
                        </span>
                    </Link>
                </template>
                <template #cell-status="{ row }"><StatusBadge group="projectStatus" :value="row.status" /></template>
                <template #cell-progress="{ row }">
                    <div class="flex items-center gap-2.5"><ProgressBar :value="row.progress" size="sm" :label="`${row.name} progress`" /><span class="w-9 text-right text-caption text-ink-3 tabular">{{ row.progress }}%</span></div>
                </template>
                <template #cell-budget="{ row }"><span class="tabular">{{ row.budget ? formatMoney(row.budget, 'USD', { decimals: 0 }) : '—' }}</span></template>
                <template #cell-members="{ row }"><AvatarStack :users="row.members" size="sm" /></template>
                <template #cell-due_date="{ row }">
                    <span v-if="row.due_date" class="tabular" :class="daysUntil(row.due_date) < 0 && !['completed', 'cancelled'].includes(row.status) ? 'font-medium text-danger' : 'text-ink-2'">{{ formatDate(row.due_date, 'short') }}</span>
                    <span v-else class="text-ink-3">—</span>
                </template>
                <template #mobile="{ row }">
                    <Link :href="route('projects.show', row.id)" class="block">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate font-medium text-ink">{{ row.name }}</p>
                            <StatusBadge group="projectStatus" :value="row.status" size="sm" />
                        </div>
                        <p class="text-caption text-ink-3">{{ row.client?.name ?? 'Internal' }}</p>
                        <ProgressBar :value="row.progress" size="sm" class="mt-2" :label="`${row.name} progress`" />
                    </Link>
                </template>
            </Table>
        </Card>

        <Card v-if="projects.data.length && projects.meta.last_page > 1" :padded="false" class="mt-4">
            <Pagination :meta="projects.meta" :links="projects.links" :only="['projects']" />
        </Card>
    </div>
</template>
