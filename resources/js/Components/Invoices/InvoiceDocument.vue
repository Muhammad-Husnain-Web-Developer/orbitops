<script setup>
import { computed } from 'vue';
import { formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    invoice: { type: Object, required: true },
    from: { type: Object, required: true },
});

const money = (value) => formatMoney(value, props.invoice.currency);
const qty = (value) => Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 });
const client = computed(() => props.invoice.client ?? {});
const location = computed(() => [client.value.city, client.value.country].filter(Boolean).join(', '));

const stamp = computed(() => ({
    paid: { label: 'Paid', class: 'border-success/40 text-success' },
    overdue: { label: 'Overdue', class: 'border-danger/40 text-danger' },
    cancelled: { label: 'Cancelled', class: 'border-line-strong text-ink-3' },
    draft: { label: 'Draft', class: 'border-line-strong text-ink-3' },
})[props.invoice.status] ?? null);
</script>

<template>
    <article class="paper relative overflow-hidden rounded-xl border border-line shadow-raised" :aria-label="`Invoice ${invoice.number}`">
        <div class="h-1.5 bg-accent print:hidden" aria-hidden="true" />

        <div class="p-6 sm:p-10">
            <!-- Header -->
            <header class="flex flex-col-reverse gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <img v-if="from.logo_url" :src="from.logo_url" alt="" class="size-11 rounded-lg object-cover" />
                    <span v-else class="flex size-11 items-center justify-center rounded-lg bg-accent text-body font-semibold text-accent-ink" aria-hidden="true">{{ from.name.slice(0, 2).toUpperCase() }}</span>
                    <div>
                        <p class="text-h3 text-ink">{{ from.name }}</p>
                        <p v-if="from.email" class="text-small text-ink-3">{{ from.email }}</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <p class="text-eyebrow text-ink-3">Invoice</p>
                    <p class="mt-1 font-mono text-h2 text-ink">{{ invoice.number }}</p>
                    <p v-if="stamp" class="mt-2 inline-block -rotate-3 rounded-md border-2 px-2 py-0.5 text-caption font-bold tracking-[0.12em] uppercase" :class="stamp.class">{{ stamp.label }}</p>
                </div>
            </header>

            <!-- Parties & dates -->
            <dl class="mt-10 grid grid-cols-2 gap-6 border-y border-line py-6 sm:grid-cols-4">
                <div class="col-span-2">
                    <dt class="text-caption font-medium tracking-wide text-ink-3 uppercase">Billed to</dt>
                    <dd class="mt-2 space-y-0.5 text-small text-ink-2">
                        <p class="text-body font-medium text-ink">{{ client.name }}</p>
                        <p v-if="client.contact_name">{{ client.contact_name }}</p>
                        <p v-if="client.email">{{ client.email }}</p>
                        <p v-if="client.address">{{ client.address }}</p>
                        <p v-if="location">{{ location }}</p>
                    </dd>
                </div>
                <div>
                    <dt class="text-caption font-medium tracking-wide text-ink-3 uppercase">Issued</dt>
                    <dd class="mt-2 text-body text-ink">{{ formatDate(invoice.issue_date) }}</dd>
                    <template v-if="invoice.project">
                        <dt class="mt-4 text-caption font-medium tracking-wide text-ink-3 uppercase">Project</dt>
                        <dd class="mt-2 text-body text-ink">{{ invoice.project.name }}</dd>
                    </template>
                </div>
                <div>
                    <dt class="text-caption font-medium tracking-wide text-ink-3 uppercase">Due</dt>
                    <dd class="mt-2 text-body text-ink">{{ formatDate(invoice.due_date) }}</dd>
                    <dt class="mt-4 text-caption font-medium tracking-wide text-ink-3 uppercase">Amount due</dt>
                    <dd class="mt-2 text-body font-semibold text-ink tabular">{{ money(invoice.balance) }}</dd>
                </div>
            </dl>

            <!-- Line items -->
            <table class="mt-8 w-full text-small">
                <caption class="sr-only">Line items</caption>
                <thead>
                    <tr class="border-b border-line text-caption font-medium tracking-wide text-ink-3 uppercase">
                        <th scope="col" class="pb-3 text-left font-medium">Description</th>
                        <th scope="col" class="hidden w-20 pb-3 text-right font-medium sm:table-cell">Qty</th>
                        <th scope="col" class="hidden w-28 pb-3 text-right font-medium sm:table-cell">Rate</th>
                        <th scope="col" class="w-28 pb-3 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in invoice.items" :key="item.id" class="border-b border-line align-top">
                        <td class="py-3.5 pr-4 text-ink">
                            {{ item.description }}
                            <span class="mt-0.5 block text-caption text-ink-3 tabular sm:hidden">{{ qty(item.quantity) }} × {{ money(item.unit_price) }}</span>
                        </td>
                        <td class="hidden py-3.5 text-right text-ink-2 tabular sm:table-cell">{{ qty(item.quantity) }}</td>
                        <td class="hidden py-3.5 text-right text-ink-2 tabular sm:table-cell">{{ money(item.unit_price) }}</td>
                        <td class="py-3.5 text-right font-medium text-ink tabular">{{ money(item.amount) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="mt-6 flex justify-end">
                <dl class="w-full max-w-xs space-y-2.5 text-small">
                    <div class="flex justify-between text-ink-2"><dt>Subtotal</dt><dd class="tabular">{{ money(invoice.subtotal) }}</dd></div>
                    <div v-if="invoice.discount_amount > 0" class="flex justify-between text-ink-2">
                        <dt>Discount<template v-if="invoice.discount_type === 'percent'"> ({{ invoice.discount_value }}%)</template></dt>
                        <dd class="tabular">−{{ money(invoice.discount_amount) }}</dd>
                    </div>
                    <div v-if="invoice.tax_rate > 0" class="flex justify-between text-ink-2"><dt>Tax ({{ invoice.tax_rate }}%)</dt><dd class="tabular">{{ money(invoice.tax_amount) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2.5 text-body font-semibold text-ink"><dt>Total</dt><dd class="tabular">{{ money(invoice.total) }}</dd></div>
                    <div v-if="invoice.amount_paid > 0" class="flex justify-between text-success"><dt>Paid</dt><dd class="tabular">−{{ money(invoice.amount_paid) }}</dd></div>
                    <div class="flex items-baseline justify-between rounded-lg bg-subtle px-3 py-2.5 text-ink">
                        <dt class="font-medium">Balance due</dt>
                        <dd class="text-h3 tabular">{{ money(invoice.balance) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Notes & terms -->
            <div v-if="invoice.notes || invoice.terms" class="mt-10 grid gap-6 border-t border-line pt-6 sm:grid-cols-2">
                <div v-if="invoice.notes">
                    <h3 class="text-caption font-medium tracking-wide text-ink-3 uppercase">Notes</h3>
                    <p class="mt-2 text-small whitespace-pre-line text-ink-2">{{ invoice.notes }}</p>
                </div>
                <div v-if="invoice.terms">
                    <h3 class="text-caption font-medium tracking-wide text-ink-3 uppercase">Terms</h3>
                    <p class="mt-2 text-small whitespace-pre-line text-ink-2">{{ invoice.terms }}</p>
                </div>
            </div>

            <p class="mt-10 text-center text-caption text-ink-3">{{ from.name }} · Invoice {{ invoice.number }} · Generated with OrbitOps</p>
        </div>
    </article>
</template>
