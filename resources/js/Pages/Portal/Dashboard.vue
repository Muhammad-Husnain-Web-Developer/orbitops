<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CalendarClock, CheckCircle2, FileText, FolderKanban, MessageSquare, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import FileIcon from '@/Components/Files/FileIcon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { swatch } from '@/lib/colors';
import { daysUntil, formatBytes, formatDate, formatMoney, formatRelative } from '@/lib/format';

const props = defineProps({
    client: { type: Object, required: true },
    projects: { type: Array, required: true },
    approvals: { type: Array, required: true },
    invoices: { type: Array, required: true },
    balance: { type: Number, default: 0 },
    messages: { type: Array, required: true },
    files: { type: Array, required: true },
    projectNames: { type: Object, default: () => ({}) },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);
const workspace = computed(() => page.props.workspace);
const greeting = computed(() => {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
});

const currency = computed(() => props.invoices[0]?.currency ?? workspace.value.currency ?? 'USD');
const dueText = (date) => {
    const days = daysUntil(date);
    if (days < 0) return { text: `${Math.abs(days)} ${Math.abs(days) === 1 ? 'day' : 'days'} overdue`, class: 'text-danger' };
    if (days === 0) return { text: 'Due today', class: 'text-warning' };
    return { text: `Due in ${days} ${days === 1 ? 'day' : 'days'}`, class: 'text-ink-3' };
};
</script>

<template>
    <Head title="Overview" />

    <div class="mb-8">
        <p class="text-small text-ink-3">{{ formatDate(new Date(), 'long') }}</p>
        <h1 class="mt-1 text-h2 text-ink sm:text-[1.875rem] sm:leading-tight sm:tracking-[-0.03em]">{{ greeting }}, {{ firstName }}</h1>
        <p class="mt-1 text-body text-ink-3">Here's where things stand with {{ workspace.name }}.</p>
    </div>

    <!-- Needs your attention -->
    <Link
        v-if="approvals.length"
        :href="route('portal.approvals')"
        class="group mb-6 flex items-center gap-4 rounded-xl border border-accent/30 bg-accent/8 p-4 transition-colors hover:bg-accent/12 sm:p-5"
    >
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-ink"><CheckCircle2 class="size-5" /></span>
        <span class="min-w-0 flex-1">
            <span class="block text-body font-semibold text-ink">{{ approvals.length === 1 ? 'A milestone is ready for your review' : `${approvals.length} milestones are ready for your review` }}</span>
            <span class="block truncate text-small text-ink-2">{{ approvals.map((item) => `${item.name} · ${item.project?.name}`).join(', ') }}</span>
        </span>
        <span class="hidden items-center gap-1 text-small font-medium text-accent-text sm:flex">Review <ArrowRight class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
    </Link>

    <dl class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="flex items-center gap-2 text-small text-ink-3"><FolderKanban class="size-4" />Active projects</dt>
            <dd class="mt-1.5 text-h2 text-ink tabular">{{ projects.length }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="flex items-center gap-2 text-small text-ink-3"><Wallet class="size-4" />Balance due</dt>
            <dd class="mt-1.5 text-h2 tabular" :class="balance > 0 ? 'text-ink' : 'text-success'">{{ formatMoney(balance, currency) }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="flex items-center gap-2 text-small text-ink-3"><CheckCircle2 class="size-4" />Awaiting your review</dt>
            <dd class="mt-1.5 text-h2 text-ink tabular">{{ approvals.length }}</dd>
        </div>
    </dl>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-4 lg:col-span-2" aria-labelledby="projects-heading">
            <div class="flex items-center justify-between">
                <h2 id="projects-heading" class="text-h3 text-ink">Your projects</h2>
                <Link :href="route('portal.projects.index')" class="text-small font-medium text-accent-text hover:underline">All projects</Link>
            </div>
            <Card v-if="!projects.length">
                <EmptyState :icon="FolderKanban" title="No active projects" description="When a new project kicks off, you'll follow its progress here." compact />
            </Card>
            <Link v-for="project in projects" :key="project.id" :href="route('portal.projects.show', project.id)" class="block rounded-xl border border-line bg-surface p-5 shadow-card transition-colors hover:border-line-strong">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-body font-semibold text-ink"><span class="size-2 shrink-0 rounded-full" :class="swatch(project.color)" />{{ project.name }}</p>
                        <p v-if="project.description" class="mt-1 line-clamp-2 text-small text-ink-3">{{ project.description }}</p>
                    </div>
                    <StatusBadge group="projectStatus" :value="project.status" />
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <ProgressBar :value="project.progress" class="flex-1" :label="`${project.name} progress`" />
                    <span class="text-small font-medium text-ink tabular">{{ project.progress }}%</span>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-small text-ink-3">
                    <span v-if="project.next_milestone" class="flex items-center gap-1.5"><CalendarClock class="size-4" />Next: <span class="text-ink-2">{{ project.next_milestone.name }}</span><template v-if="project.next_milestone.due_date"> · {{ formatDate(project.next_milestone.due_date, 'short') }}</template></span>
                    <span v-if="project.due_date">Launch {{ formatDate(project.due_date) }}</span>
                    <span v-if="project.lead" class="ml-auto flex items-center gap-1.5"><Avatar :user="project.lead" size="xs" decorative />{{ project.lead.name }}</span>
                </div>
            </Link>
        </section>

        <aside class="space-y-6" aria-label="Invoices, messages and files">
            <Card title="Invoices due">
                <template #actions><Link :href="route('portal.invoices.index')" class="text-small font-medium text-accent-text hover:underline">All</Link></template>
                <ul v-if="invoices.length" class="divide-y divide-line">
                    <li v-for="invoice in invoices" :key="invoice.id">
                        <Link :href="route('portal.invoices.show', invoice.id)" class="-mx-2 flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 hover:bg-hover">
                            <span class="min-w-0">
                                <span class="block font-mono text-small font-medium text-ink">{{ invoice.number }}</span>
                                <span class="block text-caption" :class="dueText(invoice.due_date).class">{{ dueText(invoice.due_date).text }}</span>
                            </span>
                            <span class="text-body font-medium text-ink tabular">{{ formatMoney(invoice.balance, invoice.currency) }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="flex items-center gap-2 text-small text-success"><CheckCircle2 class="size-4" />You're all paid up. Thank you!</p>
            </Card>

            <Card title="Latest messages">
                <template #actions><Link :href="route('portal.messages')" class="text-small font-medium text-accent-text hover:underline">Open</Link></template>
                <ul v-if="messages.length" class="space-y-4">
                    <li v-for="message in messages" :key="message.id">
                        <Link :href="route('portal.messages', { project: message.project_id })" class="flex gap-3">
                            <Avatar :user="message.author" size="sm" decorative />
                            <span class="min-w-0">
                                <span class="block truncate text-small"><span class="font-medium text-ink">{{ message.is_mine ? 'You' : message.author?.name }}</span><span class="text-ink-3"> · {{ projectNames[message.project_id] }}</span></span>
                                <span class="line-clamp-2 block text-small text-ink-2">{{ message.body }}</span>
                                <span class="block text-caption text-ink-3">{{ formatRelative(message.created_at) }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
                <EmptyState v-else :icon="MessageSquare" title="No messages yet" compact />
            </Card>

            <Card title="Recently shared files">
                <template #actions><Link :href="route('portal.files')" class="text-small font-medium text-accent-text hover:underline">All files</Link></template>
                <ul v-if="files.length" class="space-y-3">
                    <li v-for="file in files" :key="file.id" class="flex items-center gap-3">
                        <FileIcon :kind="file.kind" />
                        <span class="min-w-0 flex-1">
                            <a :href="file.download_url" class="block truncate text-small font-medium text-ink hover:underline">{{ file.name }}</a>
                            <span class="block truncate text-caption text-ink-3">{{ formatBytes(file.size) }} · {{ formatRelative(file.created_at) }}</span>
                        </span>
                    </li>
                </ul>
                <EmptyState v-else :icon="FileText" title="No files shared yet" compact />
            </Card>
        </aside>
    </div>
</template>
