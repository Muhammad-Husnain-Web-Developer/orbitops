<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { daysUntil, formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    invoices: { type: Array, required: true },
    summary: { type: Object, required: true },
});

const currency = props.invoices[0]?.currency ?? 'USD';
const due = (invoice) => {
    if (!['sent', 'overdue'].includes(invoice.status)) return null;
    const days = daysUntil(invoice.due_date);
    return days < 0 ? { text: `${Math.abs(days)}d overdue`, class: 'text-danger' } : days === 0 ? { text: 'Due today', class: 'text-warning' } : { text: `Due in ${days}d`, class: 'text-ink-3' };
};
</script>

<template>
    <Head title="Invoices" />

    <header class="mb-6">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Invoices</h1>
        <p class="mt-1 text-body text-ink-3">Every invoice we've sent you, with payment status.</p>
    </header>

    <dl class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="text-small text-ink-3">Balance due</dt>
            <dd class="mt-1 text-h2 text-ink tabular">{{ formatMoney(summary.outstanding, currency) }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="text-small text-ink-3">Overdue</dt>
            <dd class="mt-1 text-h2 tabular" :class="summary.overdue > 0 ? 'text-danger' : 'text-ink'">{{ formatMoney(summary.overdue, currency) }}</dd>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 shadow-card">
            <dt class="text-small text-ink-3">Paid to date</dt>
            <dd class="mt-1 text-h2 text-success tabular">{{ formatMoney(summary.paid, currency) }}</dd>
        </div>
    </dl>

    <Card :padded="!invoices.length">
        <ul v-if="invoices.length" class="divide-y divide-line">
            <li v-for="invoice in invoices" :key="invoice.id">
                <Link :href="route('portal.invoices.show', invoice.id)" class="flex items-center gap-4 px-4 py-3.5 transition-colors hover:bg-hover sm:px-5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-subtle text-ink-3"><FileText class="size-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-small font-medium text-ink">{{ invoice.number }}</p>
                        <p class="truncate text-caption text-ink-3">
                            <template v-if="invoice.project">{{ invoice.project.name }} · </template>Issued {{ formatDate(invoice.issue_date) }}
                            <span v-if="due(invoice)" class="font-medium" :class="due(invoice).class"> · {{ due(invoice).text }}</span>
                        </p>
                    </div>
                    <StatusBadge group="invoiceStatus" :value="invoice.status" size="sm" class="max-sm:hidden" />
                    <div class="w-28 text-right">
                        <p class="text-body font-medium text-ink tabular">{{ formatMoney(invoice.total, invoice.currency) }}</p>
                        <p v-if="invoice.amount_paid > 0 && invoice.balance > 0" class="text-caption text-ink-3 tabular">{{ formatMoney(invoice.balance, invoice.currency) }} left</p>
                        <StatusBadge group="invoiceStatus" :value="invoice.status" size="sm" class="mt-1 sm:hidden" />
                    </div>
                </Link>
            </li>
        </ul>
        <EmptyState v-else :icon="FileText" title="No invoices yet" description="Invoices will appear here once they're sent." compact />
    </Card>
</template>
