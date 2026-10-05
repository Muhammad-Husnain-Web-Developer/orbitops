<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Activity as ActivityIcon, AlarmClock, ArrowRight, Briefcase, CalendarClock, CheckSquare, Clock, FileText, FolderKanban, Gauge, Plus, TrendingUp, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import AreaChart from '@/Components/Charts/AreaChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { useRealtime } from '@/composables/useRealtime';
import { swatch } from '@/lib/colors';
import { daysUntil, dueLabel, formatDate, formatMoney, formatNumber } from '@/lib/format';

const props = defineProps({
    kpis: { type: Array, required: true },
    summary: { type: Object, required: true },
    charts: { type: Object, default: undefined },
    performance: { type: Array, default: undefined },
    activity: { type: Array, default: () => [] },
    deadlines: { type: Array, default: () => [] },
    overdueInvoices: { type: Array, default: () => [] },
    activeProjects: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = usePermissions();
const currency = computed(() => page.props.workspace.currency);

const hour = new Date().getHours();
const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
const firstName = page.props.auth.user.name.split(' ')[0];

const summaryLine = computed(() => {
    const parts = [];
    if (props.summary.due_this_week) parts.push(`${props.summary.due_this_week} task${props.summary.due_this_week === 1 ? '' : 's'} due this week`);
    if (props.summary.overdue_tasks) parts.push(`${props.summary.overdue_tasks} overdue`);
    if (props.summary.overdue_invoices) parts.push(`${props.summary.overdue_invoices} overdue invoice${props.summary.overdue_invoices === 1 ? '' : 's'}`);

    return parts.length ? `You have ${parts.join(', ')}.` : 'Nothing urgent today — a good day to get ahead.';
});

const kpiIcons = { revenue: TrendingUp, outstanding: FileText, projects: FolderKanban, utilization: Gauge, tasks: CheckSquare, hours: Clock, overdue: AlarmClock };

const quickActions = [
    { label: 'New Client', icon: Briefcase, permission: 'clients.manage', run: () => openQuickCreate('client') },
    { label: 'New Project', icon: FolderKanban, permission: 'projects.manage', run: () => openQuickCreate('project') },
    { label: 'New Task', icon: CheckSquare, permission: 'tasks.manage', run: () => openQuickCreate('task') },
    { label: 'New Invoice', icon: FileText, permission: 'invoices.manage', href: route('invoices.create') },
    { label: 'Track Time', icon: Clock, permission: 'time.track', run: () => openQuickCreate('time') },
].filter((action) => can(action.permission));

const money = (value) => formatMoney(value, currency.value, { decimals: 0 });
const compactMoney = (value) => formatMoney(value, currency.value, { compact: true });
const hours = (value) => `${formatNumber(value, { decimals: 1 })}h`;

// Brand-new workspaces get a helpful empty state instead of flat zero lines.
const hasMoney = computed(() => [...(props.charts?.revenue ?? []), ...(props.charts?.expenses ?? [])].some((value) => value > 0));
const hasHours = computed(() => [...(props.charts?.hours?.billable ?? []), ...(props.charts?.hours?.other ?? [])].some((value) => value > 0));

// Live activity: prepend new entries pushed over the workspace channel.
const liveActivity = ref([]);
const feed = computed(() => [...liveActivity.value, ...props.activity].slice(0, 8));
const pushActivity = ({ activity }) => liveActivity.value.unshift(activity);
useRealtime(() => `workspace.${page.props.workspace.id}`, { '.activity.recorded': pushActivity });
// Invoice and expense events arrive on channels that only people with access can join.
useRealtime(() => (can('invoices.view') ? `workspace.${page.props.workspace.id}.invoices` : null), { '.activity.recorded': pushActivity });
useRealtime(() => (can('expenses.view') ? `workspace.${page.props.workspace.id}.expenses` : null), { '.activity.recorded': pushActivity });
</script>

<template>
    <PageHeader :title="`${greeting}, ${firstName}`" :description="summaryLine" document-title="Overview">
        <template #eyebrow>
            <p class="mb-1 text-small text-ink-3">{{ formatDate(new Date(), 'long') }}</p>
        </template>
    </PageHeader>

    <!-- Quick actions -->
    <section v-if="quickActions.length" aria-label="Quick actions" class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        <div class="flex gap-2">
            <component
                :is="action.href ? Link : 'button'"
                v-for="action in quickActions"
                :key="action.label"
                :href="action.href"
                :type="action.href ? undefined : 'button'"
                class="group inline-flex shrink-0 items-center gap-2 rounded-xl border border-line bg-surface px-3.5 py-2.5 text-body font-medium text-ink-2 shadow-card transition-[border-color,color,transform] duration-200 hover:-translate-y-px hover:border-line-strong hover:text-ink"
                @click="action.run?.()"
            >
                <span class="flex size-6 items-center justify-center rounded-md bg-accent/10 text-accent-text transition-colors group-hover:bg-accent group-hover:text-accent-ink"><component :is="action.icon" class="size-3.5" /></span>
                {{ action.label }}
            </component>
        </div>
    </section>

    <!-- KPIs -->
    <section aria-label="Key metrics" class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard
            v-for="kpi in kpis"
            :key="kpi.key"
            :label="kpi.label"
            :value="kpi.value"
            :format="kpi.format"
            :currency="currency"
            :delta="kpi.delta"
            :delta-label="kpi.deltaLabel"
            :hint="kpi.hint"
            :up-is-good="kpi.upIsGood ?? true"
            :trend="kpi.trend"
            :icon="kpiIcons[kpi.key]"
        />
    </section>

    <!-- Charts -->
    <div class="mt-4 grid gap-4 sm:mt-6 xl:grid-cols-3">
        <Card v-if="can('reports.view')" class="xl:col-span-2" title="Revenue vs expenses" description="Paid invoices and recorded spend, last 12 months">
            <template #actions>
                <Link :href="route('reports')" class="text-small font-medium text-accent-text hover:underline">Reports</Link>
            </template>
            <div v-if="!charts" class="space-y-3" aria-busy="true" aria-label="Loading chart">
                <Skeleton class="h-3 w-40" />
                <Skeleton class="h-[240px] w-full rounded-lg" />
            </div>
            <EmptyState
                v-else-if="!hasMoney"
                :icon="TrendingUp"
                title="No revenue or spend yet"
                description="Paid invoices and recorded expenses will chart here month by month."
                compact
            >
                <Button v-if="can('invoices.manage')" size="sm" :href="route('invoices.create')">Create an invoice</Button>
            </EmptyState>
            <AreaChart
                v-else
                title="Revenue and expenses by month"
                :labels="charts.months"
                :series="[
                    { key: 'revenue', name: 'Revenue', values: charts.revenue },
                    { key: 'expenses', name: 'Expenses', values: charts.expenses },
                ]"
                :format="money"
                :format-axis="compactMoney"
            />
        </Card>

        <Card :class="can('reports.view') ? '' : 'xl:col-span-3'" title="Time tracked" :description="can('reports.view') ? 'Team hours per week' : 'Your hours per week'">
            <div v-if="!charts" class="space-y-3" aria-busy="true" aria-label="Loading chart">
                <Skeleton class="h-3 w-32" />
                <Skeleton class="h-[240px] w-full rounded-lg" />
            </div>
            <EmptyState v-else-if="!hasHours" :icon="Clock" title="No time tracked yet" description="Start a timer from any task or log time manually." compact>
                <Button v-if="can('time.track')" size="sm" variant="secondary" @click="openQuickCreate('time')">Log time</Button>
            </EmptyState>
            <BarChart
                v-else
                title="Hours tracked per week"
                :labels="charts.hours.labels"
                :series="[
                    { key: 'billable', name: 'Billable', values: charts.hours.billable },
                    { key: 'other', name: 'Non-billable', values: charts.hours.other },
                ]"
                :format="hours"
                stacked
            />
        </Card>
    </div>

    <div class="mt-4 grid gap-4 sm:mt-6 xl:grid-cols-3">
        <!-- Project performance -->
        <Card v-if="can('reports.view')" class="xl:col-span-2" title="Project performance" description="Budget burn against delivery progress" :padded="false">
            <div v-if="!performance" class="space-y-4 p-5" aria-busy="true">
                <div v-for="n in 4" :key="n" class="space-y-2"><Skeleton class="h-3.5 w-1/3" /><Skeleton class="h-2 w-full" /></div>
            </div>
            <EmptyState v-else-if="!performance.length" compact :icon="FolderKanban" title="No active projects" description="Performance appears once projects are underway." />
            <ul v-else class="divide-y divide-line">
                <li v-for="project in performance" :key="project.id" class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <span class="size-2 shrink-0 rounded-full" :class="swatch(project.color)" />
                        <Link :href="route('projects.show', project.id)" class="min-w-0 flex-1 truncate text-body font-medium text-ink hover:underline">{{ project.name }}</Link>
                        <span class="hidden text-small text-ink-3 sm:inline">{{ project.client }}</span>
                        <span class="rounded-full px-2 py-0.5 text-caption font-medium" :class="{ success: 'bg-success/10 text-success', warning: 'bg-warning/10 text-warning', danger: 'bg-danger/10 text-danger', neutral: 'bg-subtle text-ink-3' }[project.health]">
                            {{ { success: 'On track', warning: 'Watch', danger: 'Over budget', neutral: 'No budget' }[project.health] }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-4">
                        <div>
                            <div class="mb-1.5 flex justify-between text-caption text-ink-3"><span>Progress</span><span class="text-ink-2 tabular">{{ project.progress }}%</span></div>
                            <div class="h-1.5 rounded-full bg-subtle"><div class="h-full rounded-full bg-accent" :style="{ width: `${project.progress}%` }" /></div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex justify-between text-caption text-ink-3"><span>Budget used</span><span class="text-ink-2 tabular">{{ project.burn }}% of {{ compactMoney(project.budget) }}</span></div>
                            <div class="h-1.5 rounded-full bg-subtle">
                                <div class="h-full rounded-full" :class="{ success: 'bg-success', warning: 'bg-warning', danger: 'bg-danger', neutral: 'bg-ink-3' }[project.health]" :style="{ width: `${Math.min(100, project.burn)}%` }" />
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </Card>

        <!-- Expenses -->
        <Card v-if="can('reports.view')" title="Expenses" description="By category, last 90 days">
            <template #actions>
                <Link :href="route('expenses.index')" class="text-small font-medium text-accent-text hover:underline">View</Link>
            </template>
            <div v-if="!charts" class="flex items-center gap-6" aria-busy="true">
                <Skeleton class="size-36 rounded-full" />
                <div class="flex-1 space-y-2"><Skeleton v-for="n in 4" :key="n" class="h-3" /></div>
            </div>
            <EmptyState v-else-if="!charts.categories.length" compact :icon="Wallet" title="No expenses recorded" description="Spend you log appears here by category." />
            <DonutChart v-else title="Expenses by category" :segments="charts.categories" :format="compactMoney" center-label="90-day spend" />
        </Card>
    </div>

    <div class="mt-4 grid gap-4 sm:mt-6 xl:grid-cols-3">
        <!-- Active projects -->
        <section class="xl:col-span-2" aria-labelledby="active-projects">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="active-projects" class="text-h3 text-ink">Active projects</h2>
                <Link :href="route('projects.index')" class="inline-flex items-center gap-1 text-small font-medium text-accent-text hover:underline">All projects <ArrowRight class="size-3.5" /></Link>
            </div>
            <div v-if="activeProjects.length" class="grid gap-3 sm:grid-cols-2">
                <ProjectCard v-for="project in activeProjects" :key="project.id" :project="project" />
            </div>
            <Card v-else>
                <EmptyState :icon="FolderKanban" title="No projects yet." description="Create your first project and bring your team into the workflow.">
                    <button v-if="can('projects.manage')" type="button" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3.5 text-body font-medium text-accent-ink" @click="openQuickCreate('project')">
                        <Plus class="size-4" />Create Project
                    </button>
                </EmptyState>
            </Card>
        </section>

        <!-- Deadlines -->
        <Card title="Upcoming deadlines" description="Next 14 days" :padded="false">
            <template #actions><CalendarClock class="size-4 text-ink-3" /></template>
            <EmptyState v-if="!deadlines.length" compact :icon="CalendarClock" title="Nothing due soon" description="Tasks and milestones due in the next two weeks show up here." />
            <ul v-else class="divide-y divide-line">
                <li v-for="item in deadlines" :key="item.id">
                    <Link :href="item.url" class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-hover">
                        <span class="size-2 shrink-0 rounded-full" :class="item.type === 'milestone' ? 'rotate-45 rounded-[2px] ' + swatch(item.color) : swatch(item.color)" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-body font-medium text-ink">{{ item.title }}</span>
                            <span class="block truncate text-caption text-ink-3">{{ item.meta }}</span>
                        </span>
                        <span class="shrink-0 text-caption font-medium" :class="daysUntil(item.due_date) < 0 ? 'text-danger' : daysUntil(item.due_date) <= 2 ? 'text-warning' : 'text-ink-3'">{{ dueLabel(item.due_date) }}</span>
                    </Link>
                </li>
            </ul>
        </Card>
    </div>

    <div class="mt-4 grid gap-4 sm:mt-6 xl:grid-cols-3">
        <!-- Activity -->
        <Card v-if="can('activity.view')" class="xl:col-span-2" title="Recent activity" :padded="false">
            <template #actions>
                <span v-if="page.props.app.realtime" class="inline-flex items-center gap-1.5 text-caption text-ink-3"><span class="size-1.5 animate-pulse rounded-full bg-success" />Live</span>
                <Link :href="route('activity')" class="text-small font-medium text-accent-text hover:underline">View all</Link>
            </template>
            <EmptyState v-if="!feed.length" compact :icon="ActivityIcon" title="No activity yet" description="Actions across your workspace will appear here as they happen." />
            <ActivityFeed v-else :items="feed" class="px-5 py-4" />
        </Card>

        <!-- Overdue invoices -->
        <Card v-if="can('invoices.view')" title="Overdue invoices" :padded="false">
            <template #actions>
                <Link :href="route('invoices.index', { status: 'overdue' })" class="text-small font-medium text-accent-text hover:underline">View</Link>
            </template>
            <EmptyState v-if="!overdueInvoices.length" compact :icon="FileText" title="Nothing overdue" description="Every sent invoice is within its payment terms." />
            <ul v-else class="divide-y divide-line">
                <li v-for="invoice in overdueInvoices" :key="invoice.id">
                    <Link :href="route('invoices.show', invoice.id)" class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-hover">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-danger/10 text-danger"><AlarmClock class="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-body font-medium text-ink">{{ invoice.client.name }}</span>
                            <span class="block text-caption text-ink-3">{{ invoice.number }} · {{ dueLabel(invoice.due_date) }}</span>
                        </span>
                        <span class="text-body font-semibold text-ink tabular">{{ formatMoney(invoice.balance, invoice.currency) }}</span>
                    </Link>
                </li>
            </ul>
        </Card>
    </div>
</template>
