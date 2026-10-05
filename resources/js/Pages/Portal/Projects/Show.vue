<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, CheckCircle2, Circle, CircleDot, FileText, Flag, ListChecks, MessageSquare, Paperclip } from '@lucide/vue';
import { computed } from 'vue';
import FileIcon from '@/Components/Files/FileIcon.vue';
import MessageThread from '@/Components/Portal/MessageThread.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { useUrlTab } from '@/composables/useUrlTab';
import { swatch } from '@/lib/colors';
import { formatBytes, formatDate, formatMoney, formatRelative } from '@/lib/format';

const props = defineProps({
    project: { type: Object, required: true },
    milestones: { type: Array, required: true },
    tasks: { type: Array, required: true },
    files: { type: Array, required: true },
    messages: { type: Array, required: true },
    invoices: { type: Array, required: true },
    tab: { type: String, default: 'overview' },
});

const page = usePage();
const tab = useUrlTab(props.tab);

const tabs = computed(() => [
    { key: 'overview', label: 'Overview', icon: Flag },
    { key: 'tasks', label: 'Tasks', icon: ListChecks, count: props.tasks.length },
    { key: 'files', label: 'Files', icon: Paperclip, count: props.files.length },
    { key: 'messages', label: 'Messages', icon: MessageSquare, count: props.messages.length },
    { key: 'invoices', label: 'Invoices', icon: FileText, count: props.invoices.length },
]);

const columns = [
    { key: 'todo', label: 'Up next', statuses: ['backlog', 'todo'] },
    { key: 'in_progress', label: 'In progress', statuses: ['in_progress', 'review'] },
    { key: 'done', label: 'Done', statuses: ['done'] },
];
const tasksIn = (statuses) => props.tasks.filter((task) => statuses.includes(task.status));

const milestoneState = (milestone) => {
    if (milestone.approval_status === 'pending') return { label: 'Awaiting your approval', tone: 'accent', icon: CircleDot };
    if (milestone.approval_status === 'changes_requested') return { label: 'Changes requested', tone: 'warning', icon: CircleDot };
    if (milestone.status === 'completed') return { label: milestone.approval_status === 'approved' ? 'Approved' : 'Delivered', tone: 'success', icon: CheckCircle2 };
    if (milestone.status === 'in_progress') return { label: 'In progress', tone: 'info', icon: CircleDot };
    return { label: 'Upcoming', tone: 'neutral', icon: Circle };
};
</script>

<template>
    <Head :title="project.name" />

    <Link :href="route('portal.projects.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-3.5" />Projects</Link>

    <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <span class="size-3 rounded-full" :class="swatch(project.color)" />
                <h1 class="text-h2 text-ink sm:text-[1.75rem]">{{ project.name }}</h1>
                <StatusBadge group="projectStatus" :value="project.status" />
            </div>
            <p v-if="project.description" class="mt-2 max-w-2xl text-body text-ink-3">{{ project.description }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-6 rounded-xl border border-line bg-surface px-5 py-4 shadow-card">
            <div class="w-40">
                <p class="mb-1.5 flex justify-between text-small text-ink-3"><span>Progress</span><span class="font-medium text-ink tabular">{{ project.progress }}%</span></p>
                <ProgressBar :value="project.progress" size="sm" label="Project progress" />
            </div>
            <div class="text-small">
                <p class="text-ink-3">{{ project.completed_at ? 'Delivered' : 'Target launch' }}</p>
                <p class="font-medium text-ink">{{ formatDate(project.completed_at ?? project.due_date) }}</p>
            </div>
        </div>
    </header>

    <Tabs v-model="tab" :tabs="tabs" label="Project sections" class="mt-8" />

    <div class="mt-6">
        <!-- Overview: milestone timeline -->
        <div v-if="tab === 'overview'" class="grid gap-6 lg:grid-cols-3">
            <Card title="Milestones" description="The big steps from kickoff to launch" class="lg:col-span-2">
                <ol v-if="milestones.length" class="relative space-y-6 before:absolute before:top-2 before:bottom-2 before:left-[11px] before:w-px before:bg-line">
                    <li v-for="milestone in milestones" :key="milestone.id" class="relative flex gap-4">
                        <span class="relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full bg-surface">
                            <component :is="milestoneState(milestone).icon" class="size-5" :class="{ 'text-success': milestoneState(milestone).tone === 'success', 'text-accent-text': milestoneState(milestone).tone === 'accent', 'text-warning': milestoneState(milestone).tone === 'warning', 'text-info': milestoneState(milestone).tone === 'info', 'text-ink-3': milestoneState(milestone).tone === 'neutral' }" />
                        </span>
                        <div class="min-w-0 flex-1 pb-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-body font-medium text-ink">{{ milestone.name }}</p>
                                <Badge :tone="milestoneState(milestone).tone" size="sm">{{ milestoneState(milestone).label }}</Badge>
                            </div>
                            <p v-if="milestone.description" class="mt-1 text-small text-ink-3">{{ milestone.description }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-caption text-ink-3"><CalendarClock class="size-3.5" />{{ milestone.completed_at ? `Delivered ${formatDate(milestone.completed_at)}` : milestone.due_date ? `Due ${formatDate(milestone.due_date)}` : 'Date to be confirmed' }}</p>
                            <Link v-if="milestone.approval_status === 'pending'" :href="route('portal.approvals')" class="mt-2 inline-flex text-small font-medium text-accent-text hover:underline">Review and approve →</Link>
                            <p v-if="milestone.approval_note" class="mt-2 rounded-lg bg-subtle px-3 py-2 text-small text-ink-2">“{{ milestone.approval_note }}”</p>
                        </div>
                    </li>
                </ol>
                <EmptyState v-else :icon="Flag" title="Milestones coming soon" description="The team will share the plan here once it's agreed." compact />
            </Card>
            <div class="space-y-6">
                <Card v-if="project.lead" title="Your project lead">
                    <div class="flex items-center gap-3">
                        <Avatar :user="project.lead" size="lg" decorative />
                        <div>
                            <p class="text-body font-medium text-ink">{{ project.lead.name }}</p>
                            <p class="text-small text-ink-3">{{ project.lead.title }}</p>
                        </div>
                    </div>
                    <button type="button" class="mt-4 text-small font-medium text-accent-text hover:underline" @click="tab = 'messages'">Send a message →</button>
                </Card>
                <Card title="At a glance">
                    <dl class="space-y-2.5 text-small">
                        <div class="flex justify-between"><dt class="text-ink-3">Started</dt><dd class="text-ink">{{ formatDate(project.start_date) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-3">Milestones</dt><dd class="text-ink tabular">{{ milestones.filter((m) => m.status === 'completed').length }} of {{ milestones.length }} done</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-3">Shared tasks</dt><dd class="text-ink tabular">{{ tasksIn(['done']).length }} of {{ tasks.length }} done</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-3">Files</dt><dd class="text-ink tabular">{{ files.length }}</dd></div>
                    </dl>
                </Card>
            </div>
        </div>

        <!-- Tasks -->
        <div v-else-if="tab === 'tasks'">
            <Card v-if="!tasks.length"><EmptyState :icon="ListChecks" title="No shared tasks yet" description="The team shares key tasks here so you can follow along." compact /></Card>
            <div v-else class="grid gap-4 md:grid-cols-3">
                <section v-for="column in columns" :key="column.key" class="rounded-xl border border-line bg-surface/60 p-3" :aria-label="column.label">
                    <h2 class="mb-3 flex items-center justify-between px-1 text-small font-medium text-ink">{{ column.label }}<span class="text-caption text-ink-3 tabular">{{ tasksIn(column.statuses).length }}</span></h2>
                    <ul class="space-y-2">
                        <li v-for="task in tasksIn(column.statuses)" :key="task.id" class="rounded-lg border border-line bg-surface p-3 shadow-card">
                            <p class="text-small font-medium" :class="task.status === 'done' ? 'text-ink-3 line-through' : 'text-ink'">{{ task.title }}</p>
                            <p class="mt-1 text-caption text-ink-3">
                                <span class="font-mono">{{ task.key }}</span>
                                <template v-if="task.status === 'review'"> · In review</template>
                                <template v-if="task.completed_at"> · Done {{ formatRelative(task.completed_at) }}</template>
                                <template v-else-if="task.due_date"> · Due {{ formatDate(task.due_date, 'short') }}</template>
                            </p>
                        </li>
                        <li v-if="!tasksIn(column.statuses).length" class="px-1 py-2 text-caption text-ink-3">Nothing here right now.</li>
                    </ul>
                </section>
            </div>
        </div>

        <!-- Files -->
        <Card v-else-if="tab === 'files'" :padded="!files.length">
            <ul v-if="files.length" class="divide-y divide-line">
                <li v-for="file in files" :key="file.id" class="flex items-center gap-3 px-5 py-3">
                    <FileIcon :kind="file.kind" />
                    <div class="min-w-0 flex-1">
                        <a :href="file.download_url" class="block truncate text-body font-medium text-ink hover:underline">{{ file.name }}</a>
                        <p class="text-caption text-ink-3">{{ formatBytes(file.size) }} · shared {{ formatRelative(file.created_at) }}<template v-if="file.uploader"> by {{ file.uploader.name }}</template></p>
                    </div>
                    <a :href="file.download_url" class="text-small font-medium text-accent-text hover:underline">Download</a>
                </li>
            </ul>
            <EmptyState v-else :icon="Paperclip" title="No files shared yet" description="Designs, documents and deliverables will appear here." compact />
        </Card>

        <!-- Messages -->
        <Card v-else-if="tab === 'messages'" class="mx-auto max-w-3xl">
            <MessageThread :messages="messages" :project-id="project.id" :only="['messages']" :team-name="page.props.workspace.name" />
        </Card>

        <!-- Invoices -->
        <Card v-else-if="tab === 'invoices'" :padded="!invoices.length">
            <ul v-if="invoices.length" class="divide-y divide-line">
                <li v-for="invoice in invoices" :key="invoice.id">
                    <Link :href="route('portal.invoices.show', invoice.id)" class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-hover">
                        <div>
                            <p class="font-mono text-small font-medium text-ink">{{ invoice.number }}</p>
                            <p class="text-caption text-ink-3">Issued {{ formatDate(invoice.issue_date) }} · due {{ formatDate(invoice.due_date) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <StatusBadge group="invoiceStatus" :value="invoice.status" size="sm" />
                            <span class="w-28 text-right text-body font-medium text-ink tabular">{{ formatMoney(invoice.total, invoice.currency) }}</span>
                        </div>
                    </Link>
                </li>
            </ul>
            <EmptyState v-else :icon="FileText" title="No invoices for this project yet" compact />
        </Card>
    </div>
</template>
