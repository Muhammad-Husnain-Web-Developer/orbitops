<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CircleDollarSign, Clock, FolderKanban, MoreHorizontal, Pencil, Play, Plus, Timer, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import TimeEntryModal from '@/Components/Time/TimeEntryModal.vue';
import TimerBar from '@/Components/Time/TimerBar.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Select from '@/Components/UI/Select.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { confirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { swatch } from '@/lib/colors';
import { formatDate, formatDuration, toDate, toDateInput } from '@/lib/format';

const props = defineProps({
    filters: { type: Object, required: true },
    week: { type: Object, required: true },
    entries: { type: Array, required: true },
    summary: { type: Object, required: true },
    projects: { type: Array, required: true },
    members: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    canViewAll: { type: Boolean, default: false },
});

const page = usePage();
const { can } = usePermissions();
const user = computed(() => page.props.auth.user);
const { filters, loading } = useFilters(props.filters, { route: 'time.index' });

const modal = ref(false);
const editing = ref(null);
const modalDefaults = ref({});

const projectOptions = computed(() => props.projects.map((project) => ({ value: String(project.id), label: project.name })));
const memberOptions = computed(() => [
    { value: 'me', label: 'My time' },
    { value: 'all', label: 'Everyone' },
    ...props.members.filter((member) => member.id !== user.value?.id).map((member) => ({ value: String(member.id), label: member.name })),
]);
const showPeople = computed(() => filters.member === 'all');

// Long days (team view) start collapsed.
const PER_DAY = 6;
const expanded = ref(new Set());
const toggleDay = (key) => (expanded.value.has(key) ? expanded.value.delete(key) : expanded.value.add(key));

// The seven days of the selected week, Monday first.
const days = computed(() => {
    const start = toDate(`${props.week.start}T00:00:00`);

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const key = toDateInput(date);

        return { key, date, label: formatDate(date, 'weekday'), isToday: key === toDateInput() };
    });
});

const dayOf = (entry) => toDateInput(toDate(entry.started_at));
const timeOf = (iso) => formatDate(iso, 'time');

const byDay = computed(() => {
    const groups = new Map(days.value.map((day) => [day.key, []]));
    props.entries.forEach((entry) => groups.get(dayOf(entry))?.push(entry));

    return days.value
        .map((day) => ({ ...day, entries: groups.get(day.key), total: groups.get(day.key).reduce((sum, entry) => sum + entry.duration_seconds, 0) }))
        .filter((day) => day.entries.length)
        .reverse();
});

const chart = computed(() => {
    const billable = days.value.map((day) => props.entries.filter((entry) => entry.billable && dayOf(entry) === day.key).reduce((sum, entry) => sum + entry.duration_seconds, 0) / 3600);
    const other = days.value.map((day) => props.entries.filter((entry) => !entry.billable && dayOf(entry) === day.key).reduce((sum, entry) => sum + entry.duration_seconds, 0) / 3600);

    return {
        labels: days.value.map((day) => day.label),
        series: [
            { key: 'billable', name: 'Billable', values: billable.map((value) => Math.round(value * 10) / 10) },
            { key: 'other', name: 'Non-billable', values: other.map((value) => Math.round(value * 10) / 10) },
        ],
    };
});

// Timesheet: one row per project, one column per day.
const timesheet = computed(() => {
    const rows = new Map();

    props.entries.forEach((entry) => {
        if (!rows.has(entry.project_id)) {
            rows.set(entry.project_id, { project: entry.project, days: Object.fromEntries(days.value.map((day) => [day.key, 0])), total: 0 });
        }

        const row = rows.get(entry.project_id);
        const key = dayOf(entry);
        if (key in row.days) row.days[key] += entry.duration_seconds;
        row.total += entry.duration_seconds;
    });

    return [...rows.values()].sort((a, b) => b.total - a.total);
});

const dayTotals = computed(() => days.value.map((day) => timesheet.value.reduce((sum, row) => sum + row.days[day.key], 0)));

const rangeLabel = computed(() => {
    const start = toDate(`${props.week.start}T00:00:00`);
    const end = toDate(`${props.week.end}T00:00:00`);
    const sameMonth = start.getMonth() === end.getMonth();

    return `${formatDate(start, 'short')} – ${sameMonth ? end.getDate() : formatDate(end, 'short')}, ${end.getFullYear()}`;
});

const billableShare = computed(() => (props.summary.total ? Math.round((props.summary.billable / props.summary.total) * 100) : 0));
const dailyAverage = computed(() => (props.summary.days_logged ? Math.round(props.summary.total / props.summary.days_logged) : 0));

function goToWeek(week) {
    filters.week = week;
}

function logTime(defaults = {}) {
    editing.value = null;
    modalDefaults.value = defaults;
    modal.value = true;
}

function edit(entry) {
    editing.value = entry;
    modal.value = true;
}

function restart(entry) {
    router.post(route('timer.start'), { project_id: entry.project_id, task_id: entry.task_id, description: entry.description, billable: entry.billable }, { preserveScroll: true });
}

async function remove(entry) {
    if (await confirm({ title: 'Delete this time entry?', description: `${formatDuration(entry.duration_seconds)} on ${entry.project?.name} will be removed from the timesheet.`, confirmLabel: 'Delete entry' })) {
        router.delete(route('time.destroy', entry.id), { preserveScroll: true });
    }
}

const canEdit = (entry) => entry.user_id === user.value?.id || can('time.view_all');
</script>

<template>
    <div>
        <PageHeader title="Time" description="Track hours, review the week and keep billable time accurate.">
            <template #actions>
                <Button v-if="can('time.track')" variant="secondary" :icon="Plus" @click="logTime()">Log time</Button>
            </template>
        </PageHeader>

        <TimerBar v-if="can('time.track')" :projects="projects" :recent="recent" class="mb-6" />

        <!-- Week & filters -->
        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-2">
                <div class="flex items-center rounded-lg border border-line bg-surface shadow-card">
                    <button type="button" class="flex size-9 items-center justify-center rounded-l-lg text-ink-2 hover:bg-hover hover:text-ink" aria-label="Previous week" @click="goToWeek(week.previous)"><ChevronLeft class="size-4" /></button>
                    <span class="min-w-40 border-x border-line px-3 text-center text-body font-medium text-ink tabular" aria-live="polite">{{ rangeLabel }}</span>
                    <button type="button" class="flex size-9 items-center justify-center rounded-r-lg text-ink-2 hover:bg-hover hover:text-ink" aria-label="Next week" @click="goToWeek(week.next)"><ChevronRight class="size-4" /></button>
                </div>
                <Button v-if="!week.is_current" variant="ghost" size="sm" @click="goToWeek('')">This week</Button>
            </div>
            <div class="grid grid-cols-2 gap-2 md:flex">
                <Select v-model="filters.project" :options="projectOptions" placeholder="All projects" :icon="FolderKanban" aria-label="Filter by project" class="md:w-48" />
                <Select v-if="canViewAll" v-model="filters.member" :options="memberOptions" :icon="Users" aria-label="Filter by person" class="md:w-44" />
            </div>
        </div>

        <div class="space-y-6 transition-opacity" :class="loading ? 'opacity-60' : ''" :aria-busy="loading">
            <!-- Summary -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                <StatCard label="Tracked this week" :value="summary.total" format="duration" :delta="summary.delta" :delta-label="week.is_current ? 'vs this point last week' : 'vs previous week'" :icon="Clock" />
                <StatCard label="Billable" :value="summary.billable" format="duration" :hint="`${billableShare}% of tracked time`" :icon="CircleDollarSign" />
                <StatCard label="Billable value" :value="summary.billable_amount" format="money" :currency="page.props.workspace.currency" hint="At project hourly rates" :icon="Timer" />
                <StatCard label="Daily average" :value="dailyAverage" format="duration" :hint="`${summary.days_logged} ${summary.days_logged === 1 ? 'day' : 'days'} logged`" :icon="Clock" />
            </div>

            <template v-if="entries.length">
                <div class="grid gap-6 lg:grid-cols-5">
                    <Card title="Hours by day" description="Billable and non-billable time" class="lg:col-span-3">
                        <BarChart :labels="chart.labels" :series="chart.series" stacked title="Hours by day" :format="(value) => `${value}h`" :height="220" />
                    </Card>
                    <Card title="By project" :description="`${timesheet.length} ${timesheet.length === 1 ? 'project' : 'projects'} this week`" class="lg:col-span-2">
                        <ul class="space-y-3.5">
                            <li v-for="row in timesheet.slice(0, 6)" :key="row.project.id">
                                <div class="mb-1.5 flex items-center justify-between gap-3 text-small">
                                    <span class="flex min-w-0 items-center gap-2 text-ink"><span class="size-2 shrink-0 rounded-full" :class="swatch(row.project.color)" /><span class="truncate">{{ row.project.name }}</span></span>
                                    <span class="shrink-0 font-medium text-ink tabular">{{ formatDuration(row.total) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-subtle">
                                    <div class="h-full rounded-full" :class="swatch(row.project.color)" :style="{ width: `${(row.total / summary.total) * 100}%` }" />
                                </div>
                            </li>
                        </ul>
                    </Card>
                </div>

                <!-- Timesheet grid -->
                <Card title="Timesheet" description="Hours per project and day" :padded="false" class="hidden md:block">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-small">
                            <caption class="sr-only">Timesheet for {{ rangeLabel }}</caption>
                            <thead>
                                <tr class="border-y border-line text-left text-caption text-ink-3">
                                    <th scope="col" class="px-5 py-2.5 font-medium">Project</th>
                                    <th v-for="day in days" :key="day.key" scope="col" class="w-20 px-2 py-2.5 text-right font-medium" :class="day.isToday ? 'text-accent-text' : ''">
                                        {{ day.label }} <span class="tabular">{{ day.date.getDate() }}</span>
                                    </th>
                                    <th scope="col" class="w-24 px-5 py-2.5 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in timesheet" :key="row.project.id" class="border-b border-line last:border-0 hover:bg-hover/50">
                                    <th scope="row" class="px-5 py-2.5 text-left font-normal">
                                        <span class="flex items-center gap-2 text-ink"><span class="size-2 shrink-0 rounded-full" :class="swatch(row.project.color)" /><span class="truncate">{{ row.project.name }}</span></span>
                                    </th>
                                    <td v-for="day in days" :key="day.key" class="px-2 py-2.5 text-right tabular" :class="row.days[day.key] ? 'text-ink' : 'text-ink-3/50'">
                                        {{ row.days[day.key] ? formatDuration(row.days[day.key], { compact: true }) : '—' }}
                                    </td>
                                    <td class="px-5 py-2.5 text-right font-medium text-ink tabular">{{ formatDuration(row.total, { compact: true }) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-line-strong bg-canvas/40 text-ink">
                                    <th scope="row" class="px-5 py-2.5 text-left font-medium">Total</th>
                                    <td v-for="(total, index) in dayTotals" :key="index" class="px-2 py-2.5 text-right font-medium tabular">{{ total ? formatDuration(total, { compact: true }) : '—' }}</td>
                                    <td class="px-5 py-2.5 text-right font-semibold tabular">{{ formatDuration(summary.total, { compact: true }) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <!-- Entries by day -->
                <section aria-labelledby="entries-heading" class="space-y-4">
                    <h2 id="entries-heading" class="text-h3 text-ink">Entries</h2>
                    <Card v-for="day in byDay" :key="day.key" :padded="false" as="div">
                        <div class="flex items-center justify-between border-b border-line px-4 py-2.5 sm:px-5">
                            <h3 class="text-small font-medium text-ink">
                                {{ formatDate(day.date, 'day') }}
                                <span v-if="day.isToday" class="ml-1.5 rounded-full bg-accent/10 px-1.5 py-0.5 text-caption text-accent-text">Today</span>
                            </h3>
                            <span class="text-small font-medium text-ink-2 tabular">{{ formatDuration(day.total) }}</span>
                        </div>
                        <ul class="divide-y divide-line">
                            <li v-for="entry in expanded.has(day.key) ? day.entries : day.entries.slice(0, PER_DAY)" :key="entry.id" class="group flex items-center gap-3 px-4 py-3 sm:px-5">
                                <span class="size-2 shrink-0 rounded-full" :class="swatch(entry.project?.color)" aria-hidden="true" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-body text-ink">{{ entry.description || entry.task?.title || 'No description' }}</p>
                                    <p class="truncate text-caption text-ink-3">
                                        {{ entry.project?.name }}<template v-if="entry.task && entry.description"> · {{ entry.task.title }}</template>
                                        <span class="sm:hidden"> · {{ timeOf(entry.started_at) }}–{{ timeOf(entry.ended_at) }}</span>
                                    </p>
                                </div>
                                <Avatar v-if="showPeople && entry.user" :name="entry.user.name" :src="entry.user.avatar_url" size="xs" :title="entry.user.name" class="max-sm:hidden" />
                                <CircleDollarSign class="size-4 shrink-0" :class="entry.billable ? 'text-success' : 'text-ink-3/40'" :aria-label="entry.billable ? 'Billable' : 'Non-billable'" role="img" />
                                <span class="hidden shrink-0 text-right text-small whitespace-nowrap text-ink-3 tabular sm:block">{{ timeOf(entry.started_at) }} – {{ timeOf(entry.ended_at) }}</span>
                                <span class="w-16 shrink-0 text-right text-body font-medium text-ink tabular">{{ formatDuration(entry.duration_seconds) }}</span>
                                <Dropdown v-if="canEdit(entry)" align="end" width="w-44" label="Entry actions">
                                    <template #trigger="{ attrs }">
                                        <Button v-bind="attrs" variant="ghost" size="sm" square :icon="MoreHorizontal" :aria-label="`Actions for ${entry.description || 'entry'}`" />
                                    </template>
                                    <DropdownItem v-if="entry.user_id === user?.id" :icon="Play" @select="restart(entry)">Continue</DropdownItem>
                                    <DropdownItem :icon="Pencil" @select="edit(entry)">Edit</DropdownItem>
                                    <DropdownSeparator />
                                    <DropdownItem :icon="Trash2" danger @select="remove(entry)">Delete</DropdownItem>
                                </Dropdown>
                                <span v-else class="w-8" />
                            </li>
                        </ul>
                        <button
                            v-if="day.entries.length > PER_DAY"
                            type="button"
                            class="w-full border-t border-line px-5 py-2.5 text-small font-medium text-ink-2 transition-colors hover:bg-hover hover:text-ink"
                            :aria-expanded="expanded.has(day.key)"
                            @click="toggleDay(day.key)"
                        >
                            {{ expanded.has(day.key) ? 'Show fewer' : `Show all ${day.entries.length} entries` }}
                        </button>
                    </Card>
                </section>
            </template>

            <Card v-else>
                <EmptyState :icon="Clock" :title="week.is_current ? 'No time tracked yet this week' : 'No time tracked that week'" :description="filters.project || filters.member !== 'me' ? 'Nothing matches these filters for the selected week.' : 'Start the timer above or log time you already worked.'">
                    <Button v-if="can('time.track')" :icon="Plus" @click="logTime()">Log time</Button>
                </EmptyState>
            </Card>
        </div>

        <TimeEntryModal v-model:open="modal" :entry="editing" :defaults="modalDefaults" />
    </div>
</template>
