<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { BarChart3, CalendarRange, Clock, Download, Gauge, Receipt, TrendingUp, Users, Wallet } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Input from '@/Components/UI/Input.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { swatch } from '@/lib/colors';
import { formatDate, formatDuration, formatMoney, toDateInput } from '@/lib/format';

const props = defineProps({
    period: { type: Object, required: true },
    presets: { type: Array, required: true },
    summary: { type: Object, required: true },
    cashflow: { type: Object, default: null },
    hours: { type: Object, default: null },
    categories: { type: Array, default: null },
    clients: { type: Array, default: null },
    team: { type: Array, default: null },
    projects: { type: Array, default: null },
});

const page = usePage();
const currency = computed(() => page.props.workspace?.currency ?? 'USD');
const money = (value) => formatMoney(value, currency.value, { decimals: 0 });
const compactMoney = (value) => formatMoney(value, currency.value, { compact: true });

const custom = reactive({ from: props.period.from, to: props.period.to });
const loading = ref(false);
const keys = ['period', 'summary', 'cashflow', 'hours', 'categories', 'clients', 'team', 'projects'];

function load(query) {
    // Ask for every prop (including deferred ones) so the page swaps in one go.
    router.get(route('reports'), query, {
        only: keys,
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}

function choosePreset(range) {
    if (range === 'custom') {
        load({ range, from: custom.from, to: custom.to });
        return;
    }

    load({ range });
}

function applyCustom() {
    load({ range: 'custom', from: custom.from, to: custom.to });
}

const exportUrl = computed(() => route('reports.export', props.period.range === 'custom' ? { range: 'custom', from: props.period.from, to: props.period.to } : { range: props.period.range }));

const utilizationTone = (value) => (value > 100 ? 'warning' : value >= 70 ? 'success' : value >= 40 ? 'accent' : 'neutral');
const burnTone = { danger: 'danger', warning: 'warning', success: 'success', neutral: 'neutral' };
const maxClient = computed(() => Math.max(1, ...(props.clients ?? []).map((client) => client.revenue)));
</script>

<template>
    <PageHeader title="Reports" description="Profit, cash collection, time and project health for any period.">
        <template #actions>
            <Button variant="secondary" :icon="Download" :href="exportUrl" external>Export CSV</Button>
        </template>
    </PageHeader>

    <!-- Period -->
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <Select :model-value="period.range" :options="presets" :icon="CalendarRange" aria-label="Report period" class="w-52" @update:model-value="choosePreset" />
            <form v-if="period.range === 'custom'" class="flex flex-wrap items-center gap-2" @submit.prevent="applyCustom">
                <Input v-model="custom.from" type="date" :max="custom.to" aria-label="From" class="w-40" />
                <span class="text-small text-ink-3">to</span>
                <Input v-model="custom.to" type="date" :min="custom.from" :max="toDateInput()" aria-label="To" class="w-40" />
                <Button type="submit" variant="secondary" :loading="loading">Apply</Button>
            </form>
        </div>
        <p class="text-small text-ink-3" aria-live="polite">
            {{ formatDate(period.from) }} – {{ formatDate(period.to) }}
            <template v-if="summary.comparable"> · compared with {{ formatDate(period.previous_from) }} – {{ formatDate(period.previous_to) }}</template>
            <template v-else> · not enough history for a comparison</template>
        </p>
    </div>

    <div class="space-y-6 transition-opacity" :class="loading ? 'opacity-60' : ''" :aria-busy="loading">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
            <StatCard label="Revenue" :value="summary.revenue" format="money" :currency="currency" :delta="summary.revenue_delta" delta-label="vs previous period" :icon="TrendingUp" />
            <StatCard label="Expenses" :value="summary.expenses" format="money" :currency="currency" :delta="summary.expenses_delta" delta-label="vs previous period" :up-is-good="false" :icon="Wallet" />
            <StatCard label="Profit" :value="summary.profit" format="money" :currency="currency" :delta="summary.profit_delta" :hint="summary.margin !== null ? `${summary.margin}% margin` : 'No revenue yet'" :icon="BarChart3" />
            <StatCard label="Team utilization" :value="summary.utilization" format="percent" :delta="summary.utilization_delta" delta-label="vs previous period" :icon="Gauge" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card title="Revenue vs expenses" description="Paid invoices against recorded spend, by month" class="lg:col-span-2">
                <div v-if="!cashflow" class="space-y-3" aria-busy="true" aria-label="Loading chart"><Skeleton class="h-3 w-40" /><Skeleton class="h-[240px] w-full rounded-lg" /></div>
                <BarChart
                    v-else
                    title="Revenue vs expenses by month"
                    :labels="cashflow.labels"
                    :series="[
                        { key: 'revenue', name: 'Revenue', values: cashflow.revenue },
                        { key: 'expenses', name: 'Expenses', values: cashflow.expenses },
                    ]"
                    :format="money"
                    :format-axis="compactMoney"
                />
            </Card>
            <Card title="Collections" description="Invoices issued in this period">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-small text-ink-3">Invoiced</dt>
                        <dd class="text-h2 text-ink tabular">{{ money(summary.invoiced) }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1.5 flex justify-between text-small text-ink-3"><span>Collected</span><span class="font-medium text-ink tabular">{{ summary.collected_rate }}%</span></dt>
                        <dd><ProgressBar :value="summary.collected_rate" tone="success" size="sm" label="Share of invoices collected" /></dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-t border-line pt-4">
                        <div>
                            <dt class="text-caption text-ink-3">Avg. days to pay</dt>
                            <dd class="text-h3 text-ink tabular">{{ summary.average_days_to_pay ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-caption text-ink-3">Outstanding now</dt>
                            <dd class="text-h3 text-ink tabular">{{ money(summary.outstanding) }}</dd>
                        </div>
                    </div>
                    <Link v-if="summary.overdue > 0" :href="route('invoices.index', { status: 'overdue' })" class="flex items-center justify-between rounded-lg bg-danger/8 px-3 py-2.5 text-small hover:bg-danger/12">
                        <span class="flex items-center gap-2 text-danger"><Receipt class="size-4" />Overdue</span>
                        <span class="font-medium text-danger tabular">{{ money(summary.overdue) }}</span>
                    </Link>
                </dl>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card title="Hours by week" :description="`${formatDuration(summary.hours, { compact: true })} tracked · ${summary.billable_share}% billable`" class="lg:col-span-2">
                <div v-if="!hours" class="space-y-3" aria-busy="true" aria-label="Loading chart"><Skeleton class="h-3 w-32" /><Skeleton class="h-[240px] w-full rounded-lg" /></div>
                <BarChart
                    v-else
                    title="Hours tracked per week"
                    :labels="hours.labels"
                    :series="[
                        { key: 'billable', name: 'Billable', values: hours.billable },
                        { key: 'other', name: 'Non-billable', values: hours.other },
                    ]"
                    stacked
                    :format="(value) => `${value}h`"
                />
            </Card>
            <Card title="Spend by category">
                <div v-if="!categories" class="flex items-center gap-6" aria-busy="true"><Skeleton class="size-36 rounded-full" /><div class="flex-1 space-y-2"><Skeleton v-for="n in 4" :key="n" class="h-3" /></div></div>
                <DonutChart v-else-if="categories.length" title="Spend by category" :segments="categories" :format="compactMoney" center-label="Spend" />
                <EmptyState v-else :icon="Wallet" title="No spend in this period" compact />
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card title="Revenue by client" description="Paid in this period">
                <div v-if="!clients" class="space-y-4"><div v-for="n in 4" :key="n" class="space-y-2"><Skeleton class="h-3.5 w-1/3" /><Skeleton class="h-2 w-full" /></div></div>
                <ul v-else-if="clients.length" class="space-y-4">
                    <li v-for="client in clients" :key="client.id">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-small">
                            <Link :href="route('clients.show', client.id)" class="truncate font-medium text-ink hover:underline">{{ client.name }}</Link>
                            <span class="shrink-0 text-ink-3 tabular"><span class="font-medium text-ink">{{ money(client.revenue) }}</span> · {{ client.share }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-subtle">
                            <div class="h-full rounded-full bg-[var(--chart-1)]" :style="{ width: `${(client.revenue / maxClient) * 100}%` }" />
                        </div>
                    </li>
                </ul>
                <EmptyState v-else :icon="TrendingUp" title="No payments in this period" description="Revenue appears here when invoices are paid." compact />
            </Card>

            <Card title="Team utilization" description="Tracked hours against weekly capacity">
                <div v-if="!team" class="space-y-4"><div v-for="n in 4" :key="n" class="flex items-center gap-3"><Skeleton class="size-8 rounded-full" /><div class="flex-1 space-y-2"><Skeleton class="h-3 w-1/3" /><Skeleton class="h-2 w-full" /></div></div></div>
                <ul v-else-if="team.length" class="space-y-4">
                    <li v-for="member in team" :key="member.id" class="flex items-center gap-3">
                        <Avatar :user="member" size="md" decorative />
                        <div class="min-w-0 flex-1">
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-small">
                                <span class="truncate"><span class="font-medium text-ink">{{ member.name }}</span><span v-if="member.title" class="text-ink-3"> · {{ member.title }}</span></span>
                                <span class="shrink-0 text-ink-3 tabular">{{ member.hours }}h · <span class="font-medium" :class="member.utilization > 100 ? 'text-warning' : 'text-ink'">{{ member.utilization }}%</span></span>
                            </div>
                            <ProgressBar :value="Math.min(member.utilization, 100)" :tone="utilizationTone(member.utilization)" size="sm" :label="`${member.name} utilization`" />
                        </div>
                    </li>
                </ul>
                <EmptyState v-else :icon="Users" title="No team members yet" compact />
            </Card>
        </div>

        <Card title="Project profitability" description="Time valued at project rates plus expenses, against budget and invoicing" :padded="false">
            <div v-if="!projects" class="space-y-3 p-5"><Skeleton v-for="n in 5" :key="n" class="h-8 w-full" /></div>
            <div v-else-if="projects.length" class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-small">
                    <caption class="sr-only">Project profitability</caption>
                    <thead>
                        <tr class="border-y border-line text-left text-caption text-ink-3">
                            <th scope="col" class="px-5 py-2.5 font-medium">Project</th>
                            <th scope="col" class="px-3 py-2.5 font-medium">Status</th>
                            <th scope="col" class="w-32 px-3 py-2.5 font-medium">Progress</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-medium">Hours</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-medium">Budget</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-medium">Cost</th>
                            <th scope="col" class="px-3 py-2.5 text-right font-medium">Invoiced</th>
                            <th scope="col" class="w-36 px-5 py-2.5 font-medium">Budget burn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in projects" :key="project.id" class="border-b border-line last:border-0 hover:bg-hover/50">
                            <th scope="row" class="px-5 py-3 text-left font-normal">
                                <Link :href="route('projects.show', project.id)" class="flex items-center gap-2 font-medium text-ink hover:underline"><span class="size-2 shrink-0 rounded-full" :class="swatch(project.color)" />{{ project.name }}</Link>
                                <span class="ml-4 text-caption text-ink-3">{{ project.client }}</span>
                            </th>
                            <td class="px-3 py-3"><StatusBadge group="projectStatus" :value="project.status" size="sm" /></td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2"><ProgressBar :value="project.progress" size="sm" class="flex-1" :label="`${project.name} progress`" /><span class="w-9 text-right text-caption text-ink-2 tabular">{{ project.progress }}%</span></div>
                            </td>
                            <td class="px-3 py-3 text-right text-ink-2 tabular">{{ project.hours }}</td>
                            <td class="px-3 py-3 text-right text-ink-2 tabular">{{ project.budget ? money(project.budget) : '—' }}</td>
                            <td class="px-3 py-3 text-right text-ink-2 tabular">{{ money(project.cost) }}</td>
                            <td class="px-3 py-3 text-right font-medium text-ink tabular">{{ money(project.invoiced) }}</td>
                            <td class="px-5 py-3">
                                <div v-if="project.budget" class="flex items-center gap-2">
                                    <ProgressBar :value="Math.min(project.burn, 100)" :tone="burnTone[project.health]" size="sm" class="flex-1" :label="`${project.name} budget burn`" />
                                    <span class="w-10 text-right text-caption font-medium tabular" :class="{ 'text-danger': project.health === 'danger', 'text-warning': project.health === 'warning', 'text-ink-2': !['danger', 'warning'].includes(project.health) }">{{ project.burn }}%</span>
                                </div>
                                <span v-else class="text-caption text-ink-3">Hourly</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :icon="Clock" title="No active projects in this period" compact />
        </Card>
    </div>
</template>
