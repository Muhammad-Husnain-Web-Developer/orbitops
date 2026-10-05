<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Banknote, Copy, Eye, FileText, Mail, MoreHorizontal, Pencil, Plus, SearchX, Send, Users } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import Select from '@/Components/UI/Select.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Table from '@/Components/UI/Table.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { confirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { toast } from '@/composables/useToast';
import { daysUntil, formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    invoices: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusCounts: { type: Object, required: true },
    stats: { type: Object, required: true },
    clients: { type: Array, required: true },
});

const { can } = usePermissions();
const { filters, loading, reset, sortBy } = useFilters(props.filters, { route: 'invoices.index', only: ['invoices', 'filters'], wait: 250 });

const total = computed(() => Object.values(props.statusCounts).reduce((sum, count) => sum + count, 0));
const tabs = computed(() => [
    { key: '', label: 'All', count: total.value },
    { key: 'draft', label: 'Drafts', count: props.statusCounts.draft ?? 0 },
    { key: 'sent', label: 'Sent', count: props.statusCounts.sent ?? 0 },
    { key: 'overdue', label: 'Overdue', count: props.statusCounts.overdue ?? 0 },
    { key: 'paid', label: 'Paid', count: props.statusCounts.paid ?? 0 },
    { key: 'cancelled', label: 'Cancelled', count: props.statusCounts.cancelled ?? 0 },
]);

const clientOptions = computed(() => props.clients.map((client) => ({ value: String(client.id), label: client.name })));
const hasFilters = computed(() => Boolean(filters.search || filters.client || filters.status));

const columns = [
    { key: 'number', label: 'Invoice', sortable: true },
    { key: 'client', label: 'Client' },
    { key: 'issue_date', label: 'Issued', sortable: true, hide: 'lg' },
    { key: 'due_date', label: 'Due', sortable: true, hide: 'md' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Amount', sortable: true, align: 'right' },
    { key: 'actions', label: 'Actions', srOnly: true, align: 'right', width: 'w-12' },
];

const stats = computed(() => [
    { label: 'Outstanding', value: props.stats.outstanding, hint: `${props.stats.outstanding_count} open invoices`, tone: 'text-ink' },
    { label: 'Overdue', value: props.stats.overdue, hint: props.stats.overdue_count ? `${props.stats.overdue_count} past due` : 'Nothing overdue', tone: props.stats.overdue_count ? 'text-danger' : 'text-ink' },
    { label: 'Paid · last 30 days', value: props.stats.paid_30, hint: `${props.stats.paid_30_count} invoices`, tone: 'text-success' },
    { label: 'Drafts', value: props.stats.draft, hint: `${props.stats.draft_count} not sent yet`, tone: 'text-ink' },
]);

function dueHint(invoice) {
    if (!['sent', 'overdue'].includes(invoice.status)) return null;
    const days = daysUntil(invoice.due_date);

    if (days < 0) return { text: `${Math.abs(days)}d late`, class: 'text-danger' };
    if (days === 0) return { text: 'Due today', class: 'text-warning' };
    if (days <= 7) return { text: `in ${days}d`, class: 'text-warning' };

    return null;
}

const onError = (errors) => toast.error(Object.values(errors)[0] ?? 'Something went wrong');

async function send(invoice) {
    const reminder = invoice.status !== 'draft';
    if (!invoice.client?.email) {
        toast.error(`Add a billing email to ${invoice.client?.name} first`);
        return;
    }

    if (await confirm({ title: reminder ? `Send a reminder for ${invoice.number}?` : `Send ${invoice.number}?`, description: `An email goes to ${invoice.client.email}.`, confirmLabel: reminder ? 'Send reminder' : 'Send invoice', tone: 'accent' })) {
        router.post(route('invoices.send', invoice.id), {}, { preserveScroll: true, only: ['invoices', 'statusCounts', 'stats', 'counts'], onError });
    }
}

const open = (invoice) => router.visit(route('invoices.show', invoice.id));
</script>

<template>
    <PageHeader title="Invoices" description="Bill clients, follow up on overdue payments and see what's coming in.">
        <template #actions>
            <Button v-if="can('invoices.manage')" :icon="Plus" :href="route('invoices.create')">New invoice</Button>
        </template>
    </PageHeader>

    <dl class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="stat in stats" :key="stat.label" class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card">
            <dt class="text-small text-ink-3">{{ stat.label }}</dt>
            <dd class="mt-1 text-h2 tabular" :class="stat.tone">{{ formatMoney(stat.value, $page.props.workspace.currency, { decimals: 0 }) }}</dd>
            <dd class="mt-0.5 text-caption text-ink-3">{{ stat.hint }}</dd>
        </div>
    </dl>

    <Card :padded="false">
        <div class="px-4 pt-2">
            <Tabs v-model="filters.status" :tabs="tabs" label="Filter invoices by status" />
        </div>
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
            <SearchInput v-model="filters.search" placeholder="Search by number or client…" label="Search invoices" class="sm:w-80" />
            <Select v-model="filters.client" :options="clientOptions" placeholder="All clients" :icon="Users" aria-label="Filter by client" class="sm:w-56" />
        </div>

        <Table :columns="columns" :rows="invoices.data" :loading="loading" :sort="{ key: filters.sort, direction: filters.direction }" clickable caption="Invoices" @sort="sortBy" @row-click="open">
            <template #cell-number="{ row }">
                <Link :href="route('invoices.show', row.id)" class="font-mono text-small font-medium text-ink hover:underline">{{ row.number }}</Link>
            </template>
            <template #cell-client="{ row }">
                <p class="max-w-56 truncate text-ink">{{ row.client?.name }}</p>
                <p v-if="row.project" class="max-w-56 truncate text-caption text-ink-3">{{ row.project.name }}</p>
            </template>
            <template #cell-issue_date="{ row }"><span class="tabular">{{ formatDate(row.issue_date) }}</span></template>
            <template #cell-due_date="{ row }">
                <span class="tabular">{{ formatDate(row.due_date) }}</span>
                <span v-if="dueHint(row)" class="ml-1.5 text-caption font-medium" :class="dueHint(row).class">{{ dueHint(row).text }}</span>
            </template>
            <template #cell-status="{ row }"><StatusBadge group="invoiceStatus" :value="row.status" /></template>
            <template #cell-total="{ row }">
                <p class="font-medium text-ink tabular">{{ formatMoney(row.total, row.currency) }}</p>
                <p v-if="row.amount_paid > 0 && row.balance > 0" class="text-caption text-ink-3 tabular">{{ formatMoney(row.balance, row.currency) }} due</p>
            </template>
            <template #cell-actions="{ row }">
                <Dropdown align="end" width="w-48" :label="`Actions for ${row.number}`">
                    <template #trigger="{ attrs }"><Button v-bind="attrs" variant="ghost" size="sm" square :icon="MoreHorizontal" :aria-label="`Actions for ${row.number}`" /></template>
                    <DropdownItem :icon="Eye" :href="route('invoices.show', row.id)">View</DropdownItem>
                    <template v-if="can('invoices.manage')">
                        <DropdownItem v-if="['draft', 'sent', 'overdue'].includes(row.status)" :icon="Pencil" :href="route('invoices.edit', row.id)">Edit</DropdownItem>
                        <DropdownItem v-if="row.status === 'draft'" :icon="Send" @select="send(row)">Send</DropdownItem>
                        <DropdownItem v-if="['sent', 'overdue'].includes(row.status)" :icon="Mail" @select="send(row)">Send reminder</DropdownItem>
                        <DropdownItem v-if="['sent', 'overdue'].includes(row.status)" :icon="Banknote" :href="route('invoices.show', row.id)">Record payment</DropdownItem>
                        <DropdownItem :icon="Copy" method="post" :href="route('invoices.duplicate', row.id)">Duplicate</DropdownItem>
                    </template>
                </Dropdown>
            </template>

            <template #mobile="{ row }">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-body font-medium text-ink">{{ row.client?.name }}</p>
                        <p class="text-caption text-ink-3"><span class="font-mono">{{ row.number }}</span> · Due {{ formatDate(row.due_date, 'short') }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-body font-medium text-ink tabular">{{ formatMoney(row.total, row.currency) }}</p>
                        <StatusBadge group="invoiceStatus" :value="row.status" size="sm" class="mt-1" />
                    </div>
                </div>
            </template>

            <template #empty>
                <EmptyState v-if="hasFilters" :icon="SearchX" title="No invoices match" description="Try a different status, client or search term." compact>
                    <Button variant="secondary" @click="reset({ sort: filters.sort, direction: filters.direction })">Clear filters</Button>
                </EmptyState>
                <EmptyState v-else :icon="FileText" title="No invoices yet" description="Create your first invoice and send it to a client in under a minute.">
                    <Button v-if="can('invoices.manage')" :icon="Plus" :href="route('invoices.create')">New invoice</Button>
                </EmptyState>
            </template>
        </Table>

        <Pagination v-if="invoices.meta.last_page > 1" :meta="invoices.meta" :links="invoices.links" :only="['invoices', 'filters']" class="border-t border-line" />
    </Card>
</template>
