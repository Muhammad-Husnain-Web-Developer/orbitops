<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Ban, CheckCircle2, CircleDollarSign, FolderKanban, MoreHorizontal, Paperclip, Pencil, Plus, Receipt, RotateCcw, SearchX, Tag, Trash2, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import ExpenseFormModal from '@/Components/Expenses/ExpenseFormModal.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import Select from '@/Components/UI/Select.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Table from '@/Components/UI/Table.vue';
import { confirm } from '@/composables/useConfirm';
import { useEnums } from '@/composables/useEnums';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { swatch } from '@/lib/colors';
import { formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    expenses: { type: Object, required: true },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    projects: { type: Array, required: true },
});

const page = usePage();
const { can } = usePermissions();
const currency = computed(() => page.props.workspace?.currency ?? 'USD');
const { options, label } = useEnums();
const { filters, loading, reset, sortBy } = useFilters(props.filters, { route: 'expenses.index', only: ['expenses', 'filters', 'charts'], wait: 250 });

const modal = ref(false);
const editing = ref(null);

const periods = [
    { value: 'month', label: 'This month' },
    { value: 'last_month', label: 'Last month' },
    { value: 'quarter', label: 'Last 90 days' },
    { value: 'year', label: 'This year' },
    { value: 'all', label: 'All time' },
];
const statuses = [{ value: '', label: 'All' }, ...options('expenseStatus')];
const projectOptions = computed(() => [{ value: 'none', label: 'Overhead (no project)' }, ...props.projects.map((project) => ({ value: String(project.id), label: project.name }))]);
const hasFilters = computed(() => Boolean(filters.search || filters.status || filters.category || filters.project || filters.mine));

const columns = [
    { key: 'spent_on', label: 'Date', sortable: true, width: 'w-28' },
    { key: 'vendor', label: 'Expense', sortable: true },
    { key: 'category', label: 'Category', hide: 'lg' },
    { key: 'user', label: 'Submitted by', hide: 'xl' },
    { key: 'status', label: 'Status' },
    { key: 'amount', label: 'Amount', sortable: true, align: 'right' },
    { key: 'actions', label: 'Actions', srOnly: true, align: 'right', width: 'w-12' },
];

const money = (value, code) => formatMoney(value, code ?? currency.value);
const compactMoney = (value) => formatMoney(value, currency.value, { compact: true });
const periodLabel = computed(() => periods.find((period) => period.value === filters.period)?.label ?? '');

function create() {
    editing.value = null;
    modal.value = true;
}

function edit(expense) {
    editing.value = expense;
    modal.value = true;
}

function setStatus(expense, status) {
    router.patch(route('expenses.status', expense.id), { status }, { preserveScroll: true, only: ['expenses', 'stats', 'charts'] });
}

async function remove(expense) {
    if (await confirm({ title: 'Delete this expense?', description: `${expense.vendor ?? expense.description} · ${money(expense.amount, expense.currency)} will be removed.`, confirmLabel: 'Delete expense' })) {
        router.delete(route('expenses.destroy', expense.id), { preserveScroll: true, only: ['expenses', 'stats', 'charts'] });
    }
}

const canEdit = (expense, userId) => can('expenses.approve') || (can('expenses.manage') && expense.user?.id === userId && expense.status === 'pending');
</script>

<template>
    <PageHeader title="Expenses" description="Track spend, attach receipts and approve costs before they hit the books.">
        <template #actions>
            <Button v-if="can('expenses.manage')" :icon="Plus" @click="create">Add expense</Button>
        </template>
    </PageHeader>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <StatCard label="Spent this month" :currency="currency" :value="stats.month" format="money" :delta="stats.month_delta" delta-label="vs same point last month" :up-is-good="false" :icon="Wallet" />
        <StatCard label="Awaiting approval" :currency="currency" :value="stats.pending" format="money" :hint="`${stats.pending_count} ${stats.pending_count === 1 ? 'expense' : 'expenses'}`" :icon="Receipt" />
        <StatCard label="Billable to clients" :currency="currency" :value="stats.billable" format="money" hint="Last 90 days" :icon="CircleDollarSign" />
        <StatCard label="Top category" :currency="currency" :value="stats.top_category?.value ?? 0" format="money" :hint="stats.top_category ? `${stats.top_category.label} · last 90 days` : 'No spend yet'" :icon="Tag" />
    </div>

    <!-- Approval queue nudge -->
    <button
        v-if="can('expenses.approve') && stats.pending_count && filters.status !== 'pending'"
        type="button"
        class="mb-6 flex w-full items-center gap-3 rounded-xl border border-warning/30 bg-warning/8 px-4 py-3 text-left transition-colors hover:bg-warning/12"
        @click="filters.status = 'pending'; filters.period = 'all'"
    >
        <Receipt class="size-4 shrink-0 text-warning" />
        <span class="flex-1 text-small text-ink"><span class="font-medium">{{ stats.pending_count }} {{ stats.pending_count === 1 ? 'expense needs' : 'expenses need' }} your approval</span> · {{ money(stats.pending) }}</span>
        <span class="text-small font-medium text-warning">Review</span>
    </button>

    <div class="mb-6 grid gap-6 lg:grid-cols-5">
        <Card title="Monthly spend" description="Project costs and overhead, last 6 months" class="lg:col-span-3">
            <BarChart
                title="Monthly spend"
                :labels="charts.months"
                :series="[
                    { key: 'project', name: 'Project costs', values: charts.project },
                    { key: 'overhead', name: 'Overhead', values: charts.overhead },
                ]"
                stacked
                :format="(value) => money(value)"
                :format-axis="compactMoney"
                :height="220"
            />
        </Card>
        <Card title="By category" :description="periodLabel" class="lg:col-span-2">
            <DonutChart v-if="charts.categories.length" title="Spend by category" :segments="charts.categories" :format="compactMoney" center-label="Total" />
            <EmptyState v-else :icon="Tag" title="No spend in this period" compact />
        </Card>
    </div>

    <Card :padded="false">
        <div class="flex flex-col gap-3 border-b border-line p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <SearchInput v-model="filters.search" placeholder="Search vendor or description…" label="Search expenses" class="lg:w-72" />
                <div class="no-scrollbar -mx-4 overflow-x-auto px-4 lg:mx-0 lg:px-0">
                    <SegmentedControl v-model="filters.status" :options="statuses" label="Filter by status" size="sm" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                <Select v-model="filters.period" :options="periods" aria-label="Period" size="sm" class="sm:w-40" />
                <Select v-model="filters.category" :options="options('expenseCategory')" placeholder="All categories" :icon="Tag" aria-label="Filter by category" size="sm" class="sm:w-44" />
                <Select v-model="filters.project" :options="projectOptions" placeholder="All projects" :icon="FolderKanban" aria-label="Filter by project" size="sm" class="sm:w-52" />
                <Checkbox v-model="filters.mine" label="Only mine" class="col-span-2 sm:ml-2" />
            </div>
        </div>

        <Table :columns="columns" :rows="expenses.data" :loading="loading" :sort="{ key: filters.sort, direction: filters.direction }" caption="Expenses" @sort="sortBy">
            <template #cell-spent_on="{ row }"><span class="tabular">{{ formatDate(row.spent_on, 'short') }}</span></template>
            <template #cell-vendor="{ row }">
                <div class="flex items-center gap-2">
                    <p class="max-w-72 truncate font-medium text-ink">{{ row.vendor || row.description }}</p>
                    <span v-if="row.billable" class="rounded bg-success/10 px-1.5 py-px text-caption font-medium text-success">Billable</span>
                    <a v-if="row.receipt_url" :href="row.receipt_url" target="_blank" rel="noopener" class="text-ink-3 hover:text-accent-text" :aria-label="`View receipt ${row.receipt_name}`" :title="row.receipt_name"><Paperclip class="size-3.5" /></a>
                </div>
                <p class="flex max-w-80 items-center gap-1.5 truncate text-caption text-ink-3">
                    <template v-if="row.vendor">{{ row.description }}</template>
                    <template v-if="row.project"><span v-if="row.vendor">·</span><span class="size-1.5 shrink-0 rounded-full" :class="swatch(row.project.color)" />{{ row.project.name }}</template>
                </p>
            </template>
            <template #cell-category="{ row }"><span class="text-ink-2">{{ label('expenseCategory', row.category) }}</span></template>
            <template #cell-user="{ row }">
                <span v-if="row.user" class="flex items-center gap-2"><Avatar :user="row.user" size="xs" decorative /><span class="truncate">{{ row.user.name }}</span></span>
            </template>
            <template #cell-status="{ row }"><StatusBadge group="expenseStatus" :value="row.status" /></template>
            <template #cell-amount="{ row }"><span class="font-medium text-ink tabular">{{ money(row.amount, row.currency) }}</span></template>
            <template #cell-actions="{ row }">
                <Dropdown v-if="canEdit(row, page.props.auth.user.id) || row.receipt_url" align="end" width="w-48" :label="`Actions for ${row.vendor || row.description}`">
                    <template #trigger="{ attrs }"><Button v-bind="attrs" variant="ghost" size="sm" square :icon="MoreHorizontal" :aria-label="`Actions for ${row.vendor || row.description}`" /></template>
                    <template v-if="can('expenses.approve')">
                        <DropdownItem v-if="row.status === 'pending'" :icon="CheckCircle2" @select="setStatus(row, 'approved')">Approve</DropdownItem>
                        <DropdownItem v-if="row.status === 'pending'" :icon="Ban" @select="setStatus(row, 'rejected')">Reject</DropdownItem>
                        <DropdownItem v-if="row.status === 'approved' && row.user" :icon="Wallet" @select="setStatus(row, 'reimbursed')">Mark reimbursed</DropdownItem>
                        <DropdownItem v-if="['approved', 'rejected'].includes(row.status)" :icon="RotateCcw" @select="setStatus(row, 'pending')">Move to pending</DropdownItem>
                    </template>
                    <DropdownItem v-if="row.receipt_url" :icon="Paperclip" :href="row.receipt_url" external>View receipt</DropdownItem>
                    <template v-if="canEdit(row, page.props.auth.user.id)">
                        <DropdownItem :icon="Pencil" @select="edit(row)">Edit</DropdownItem>
                        <DropdownSeparator />
                        <DropdownItem :icon="Trash2" danger @select="remove(row)">Delete</DropdownItem>
                    </template>
                </Dropdown>
            </template>

            <template #mobile="{ row }">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 truncate text-body font-medium text-ink">{{ row.vendor || row.description }}<Paperclip v-if="row.receipt_url" class="size-3.5 shrink-0 text-ink-3" /></p>
                        <p class="truncate text-caption text-ink-3">{{ formatDate(row.spent_on, 'short') }} · {{ label('expenseCategory', row.category) }}<template v-if="row.project"> · {{ row.project.name }}</template></p>
                    </div>
                    <div class="flex shrink-0 items-start gap-1">
                        <div class="text-right">
                            <p class="text-body font-medium text-ink tabular">{{ money(row.amount, row.currency) }}</p>
                            <StatusBadge group="expenseStatus" :value="row.status" size="sm" class="mt-1" />
                        </div>
                    </div>
                </div>
                <div v-if="can('expenses.approve') && row.status === 'pending'" class="mt-3 flex gap-2">
                    <Button size="sm" variant="secondary" :icon="CheckCircle2" class="flex-1" @click="setStatus(row, 'approved')">Approve</Button>
                    <Button size="sm" variant="ghost" :icon="Ban" class="flex-1" @click="setStatus(row, 'rejected')">Reject</Button>
                </div>
            </template>

            <template #empty>
                <EmptyState v-if="hasFilters" :icon="SearchX" title="No expenses match" description="Try another period, status or search." compact>
                    <Button variant="secondary" @click="reset({ period: filters.period, sort: filters.sort, direction: filters.direction })">Clear filters</Button>
                </EmptyState>
                <EmptyState v-else :icon="Receipt" title="No expenses in this period" description="Log software, travel and contractor costs to see where money goes.">
                    <Button v-if="can('expenses.manage')" :icon="Plus" @click="create">Add expense</Button>
                </EmptyState>
            </template>
        </Table>

        <Pagination v-if="expenses.meta.last_page > 1" :meta="expenses.meta" :links="expenses.links" :only="['expenses', 'filters']" class="border-t border-line" />
    </Card>

    <ExpenseFormModal v-model:open="modal" :expense="editing" :projects="projects" />
</template>
