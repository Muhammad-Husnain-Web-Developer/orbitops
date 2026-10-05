<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, CheckSquare, CircleCheck, Clock, MoreHorizontal, Pencil, Plus, Trash2, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import CommentThread from '@/Components/Comments/CommentThread.vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import Dropzone from '@/Components/Files/Dropzone.vue';
import FileList from '@/Components/Files/FileList.vue';
import MilestoneList from '@/Components/Projects/MilestoneList.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import KanbanBoard from '@/Components/Tasks/KanbanBoard.vue';
import TaskDrawer from '@/Components/Tasks/TaskDrawer.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import ProgressRing from '@/Components/UI/ProgressRing.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { confirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { useRealtime } from '@/composables/useRealtime';
import { useTaskDrawer } from '@/composables/useTaskDrawer';
import { useUrlTab } from '@/composables/useUrlTab';
import { solidSwatch, swatch } from '@/lib/colors';
import { dueLabel, formatDate, formatDuration, formatMoney } from '@/lib/format';

const props = defineProps({
    project: { type: Object, required: true },
    tab: { type: String, default: 'overview' },
    stats: { type: Object, required: true },
    milestones: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
    files: { type: Array, default: () => [] },
    messages: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    activeTask: { type: Object, default: null },
});

const page = usePage();
const { can } = usePermissions();
const tab = useUrlTab(props.tab);
const editing = ref(false);
const { open: drawerOpen, loading: drawerLoading, openTask } = useTaskDrawer(() => props.activeTask);

const openTasks = computed(() => props.tasks.filter((task) => task.status !== 'done').length);
const tabs = computed(() =>
    [
        { key: 'overview', label: 'Overview' },
        { key: 'board', label: 'Board', count: openTasks.value },
        { key: 'milestones', label: 'Milestones', count: props.milestones.length },
        can('files.view') && { key: 'files', label: 'Files', count: props.files.length },
        { key: 'messages', label: 'Client messages', count: props.messages.length },
        { key: 'activity', label: 'Activity' },
    ].filter(Boolean),
);

const performance = computed(() => props.stats.performance ?? {});
const money = (value) => formatMoney(value, page.props.workspace.currency, { decimals: 0 });
const healthTone = { success: 'success', warning: 'warning', danger: 'danger', neutral: 'neutral' };
const healthLabel = { success: 'On track', warning: 'Watch budget', danger: 'Over budget', neutral: 'No budget set' };

useRealtime(() => `workspace.${page.props.workspace.id}`, {
    '.task.moved': (event) => event.project_id === props.project.id && event.actor_id !== page.props.auth.user.id && router.reload({ only: ['tasks'] }),
});

function newTask(status = 'todo') {
    openQuickCreate('task', { project_id: props.project.id, status, milestones: props.milestones });
}

function complete() {
    router.put(route('projects.update', props.project.id), { ...projectPayload(), status: 'completed' }, { preserveScroll: true });
}

function projectPayload() {
    const p = props.project;

    return {
        name: p.name, code: p.code, client_id: p.client_id ?? '', owner_id: p.owner_id, description: p.description, status: p.status, priority: p.priority, color: p.color,
        billing_type: p.billing_type, budget: p.budget, hourly_rate: p.hourly_rate ?? '', start_date: p.start_date, due_date: p.due_date, member_ids: p.members.map((m) => m.id),
    };
}

async function remove() {
    if (await confirm({ title: `Delete ${props.project.name}?`, description: 'Tasks, milestones and files are removed from views. This cannot be undone from the app.', confirmLabel: 'Delete project', requireText: props.project.code || props.project.name })) {
        router.delete(route('projects.destroy', props.project.id));
    }
}
</script>

<template>
    <Head :title="project.name" />

    <Link :href="route('projects.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-3.5" />Projects</Link>

    <header class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-4">
            <span class="mt-1 flex size-12 shrink-0 items-center justify-center rounded-xl font-mono text-small font-bold text-white shadow-card" :class="solidSwatch(project.color)">{{ (project.code || project.name).slice(0, 4) }}</span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-h2 text-ink">{{ project.name }}</h1>
                    <StatusBadge group="projectStatus" :value="project.status" />
                    <Badge :tone="healthTone[performance.health]" size="sm">{{ healthLabel[performance.health] }}</Badge>
                </div>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-body text-ink-3">
                    <Link v-if="project.client" :href="route('clients.show', project.client.id)" class="hover:text-ink hover:underline">{{ project.client.name }}</Link>
                    <span v-else>Internal project</span>
                    <span v-if="project.due_date" class="inline-flex items-center gap-1"><CalendarDays class="size-3.5" />{{ dueLabel(project.due_date) }}</span>
                    <span v-if="project.owner" class="inline-flex items-center gap-1.5"><Avatar :user="project.owner" size="xs" decorative />Led by {{ project.owner.name }}</span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Button v-if="can('tasks.manage')" :icon="Plus" @click="newTask()">Add task</Button>
            <Button v-if="can('projects.manage')" variant="secondary" :icon="Pencil" @click="editing = true">Edit</Button>
            <Dropdown v-if="can('projects.manage')" align="end" label="Project actions">
                <template #trigger="{ attrs }"><Button v-bind="attrs" variant="secondary" square :icon="MoreHorizontal" aria-label="Project actions" /></template>
                <DropdownItem v-if="project.status !== 'completed'" :icon="CircleCheck" @select="complete">Mark as completed</DropdownItem>
                <DropdownItem v-if="can('time.track')" :icon="Clock" @select="openQuickCreate('time', { project_id: project.id })">Log time</DropdownItem>
                <template v-if="can('projects.delete')">
                    <DropdownSeparator />
                    <DropdownItem :icon="Trash2" danger @select="remove">Delete project</DropdownItem>
                </template>
            </Dropdown>
        </div>
    </header>

    <Tabs v-model="tab" :tabs="tabs" label="Project sections" class="mt-8" />

    <div class="mt-6">
        <!-- Overview -->
        <div v-if="tab === 'overview'" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-4 rounded-xl border border-line bg-surface p-4 shadow-card">
                    <ProgressRing :value="project.progress ?? 0" :size="52" label="Project progress" />
                    <div><p class="text-small text-ink-3">Progress</p><p class="text-h3 text-ink">{{ project.progress ?? 0 }}% complete</p></div>
                </div>
                <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
                    <p class="flex items-center gap-1.5 text-small text-ink-3"><CheckSquare class="size-3.5" />Tasks</p>
                    <p class="mt-1 text-h2 text-ink tabular">{{ project.completed_tasks_count }} <span class="text-ink-3">/ {{ project.tasks_count }}</span></p>
                </div>
                <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
                    <p class="flex items-center gap-1.5 text-small text-ink-3"><Clock class="size-3.5" />Hours tracked</p>
                    <p class="mt-1 text-h2 text-ink tabular">{{ formatDuration(stats.tracked_seconds, { compact: true }) }}</p>
                    <p class="text-caption text-ink-3">{{ formatDuration(stats.billable_seconds, { compact: true }) }} billable</p>
                </div>
                <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
                    <p class="flex items-center gap-1.5 text-small text-ink-3"><Wallet class="size-3.5" />Budget</p>
                    <p class="mt-1 text-h2 text-ink tabular">{{ project.budget ? money(project.budget) : '—' }}</p>
                    <div v-if="project.budget" class="mt-2"><ProgressBar :value="Math.min(100, performance.burn ?? 0)" :tone="performance.health === 'danger' ? 'danger' : performance.health === 'warning' ? 'warning' : 'success'" size="sm" label="Budget used" /><p class="mt-1 text-caption text-ink-3">{{ performance.burn ?? 0 }}% used · {{ money(performance.cost ?? 0) }}</p></div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card title="About this project" class="lg:col-span-2">
                    <p class="text-body whitespace-pre-line text-ink-2">{{ project.description || 'No description yet.' }}</p>
                    <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-line pt-5 text-body sm:grid-cols-4">
                        <div><dt class="text-caption text-ink-3">Billing</dt><dd class="mt-0.5 font-medium text-ink capitalize">{{ project.billing_type }}<template v-if="project.hourly_rate"> · {{ money(project.hourly_rate) }}/h</template></dd></div>
                        <div><dt class="text-caption text-ink-3">Start</dt><dd class="mt-0.5 font-medium text-ink">{{ formatDate(project.start_date) }}</dd></div>
                        <div><dt class="text-caption text-ink-3">Due</dt><dd class="mt-0.5 font-medium text-ink">{{ formatDate(project.due_date) }}</dd></div>
                        <div v-if="stats.invoiced !== null"><dt class="text-caption text-ink-3">Invoiced</dt><dd class="mt-0.5 font-medium text-ink">{{ money(stats.invoiced) }}</dd></div>
                    </dl>
                </Card>
                <Card title="Team">
                    <ul class="space-y-3">
                        <li v-for="member in project.members" :key="member.id" class="flex items-center gap-3">
                            <Avatar :user="member" size="md" decorative />
                            <div class="min-w-0"><p class="truncate text-body font-medium text-ink">{{ member.name }}</p><p class="truncate text-caption text-ink-3">{{ member.title ?? 'Team member' }}</p></div>
                            <Badge v-if="member.id === project.owner_id" size="sm" tone="accent" class="ml-auto">Lead</Badge>
                        </li>
                    </ul>
                </Card>
                <Card title="Milestones" class="lg:col-span-2">
                    <template #actions><button type="button" class="text-small font-medium text-accent-text hover:underline" @click="tab = 'milestones'">Manage</button></template>
                    <MilestoneList :project-id="project.id" :milestones="milestones" />
                </Card>
                <Card title="Recent activity">
                    <EmptyState v-if="!activity.length" compact :icon="Clock" title="No activity yet" description="Changes to this project will appear here." />
                    <ActivityFeed v-else :items="activity.slice(0, 5)" />
                </Card>
            </div>
        </div>

        <!-- Board -->
        <div v-else-if="tab === 'board'">
            <Card v-if="!tasks.length">
                <EmptyState :icon="CheckSquare" title="No tasks yet." description="Break this project into tasks and move them across the board as work progresses.">
                    <Button v-if="can('tasks.manage')" :icon="Plus" @click="newTask()">Create Task</Button>
                </EmptyState>
            </Card>
            <KanbanBoard v-else :tasks="tasks" :show-project="false" :readonly="!can('tasks.manage')" @open="openTask($event.id)" @create="newTask" />
        </div>

        <MilestoneList v-else-if="tab === 'milestones'" :project-id="project.id" :milestones="milestones" :editable="can('projects.manage')" />

        <Card v-else-if="tab === 'files'" title="Files" description="Share a file with the client to make it visible in their portal.">
            <Dropzone v-if="can('files.manage')" :data="{ project_id: project.id }" :only="['files', 'activity']" class="mb-4" />
            <FileList :files="files" :only="['files']" />
        </Card>

        <Card v-else-if="tab === 'messages'" title="Client conversation" :description="project.client ? `Messages here are visible to ${project.client.name} in their portal.` : 'Link a client to start a conversation.'">
            <CommentThread
                :comments="messages"
                :action="project.client ? route('projects.messages.store', project.id) : null"
                :only="['messages']"
                variant="chat"
                placeholder="Reply to the client…"
                empty-title="No messages yet"
                empty-description="Questions and updates between your team and the client appear here."
                submit-label="Send"
            />
        </Card>

        <Card v-else-if="tab === 'activity'" title="Activity">
            <EmptyState v-if="!activity.length" compact :icon="Clock" title="No activity yet" description="Changes to this project will appear here." />
            <ActivityFeed v-else :items="activity" grouped />
        </Card>
    </div>

    <TaskDrawer v-model:open="drawerOpen" :task="activeTask" :loading="drawerLoading" :members="project.members" :reload-only="['tasks', 'milestones']" />
    <ProjectFormModal v-model:open="editing" :project="project" />
</template>
