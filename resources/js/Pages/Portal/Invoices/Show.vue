<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Info, Printer } from '@lucide/vue';
import { computed } from 'vue';
import InvoiceDocument from '@/Components/Invoices/InvoiceDocument.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { daysUntil, formatDate, formatMoney } from '@/lib/format';

const props = defineProps({
    invoice: { type: Object, required: true },
    from: { type: Object, required: true },
});

const outstanding = computed(() => ['sent', 'overdue'].includes(props.invoice.status));
const days = computed(() => daysUntil(props.invoice.due_date));
const print = () => window.print();
</script>

<template>
    <Head :title="`Invoice ${invoice.number}`" />

    <div class="print:hidden">
        <Link :href="route('portal.invoices.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-3.5" />Invoices</Link>
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-h2 text-ink sm:text-[1.75rem]">{{ invoice.number }}</h1>
                <StatusBadge group="invoiceStatus" :value="invoice.status" />
            </div>
            <Button variant="secondary" :icon="Printer" @click="print">Print / Save as PDF</Button>
        </header>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px] print:block">
        <InvoiceDocument :invoice="invoice" :from="from" class="min-w-0" />

        <aside class="space-y-4 print:hidden">
            <Card v-if="invoice.status === 'paid'">
                <p class="flex items-center gap-2 text-body font-medium text-success"><CheckCircle2 class="size-5" />Paid in full</p>
                <p class="mt-1 text-small text-ink-3">Received {{ formatDate(invoice.paid_at) }}. Thank you!</p>
            </Card>
            <Card v-else-if="outstanding">
                <p class="text-small text-ink-3">Amount due</p>
                <p class="mt-1 text-[2rem] leading-none font-semibold tracking-tight text-ink tabular">{{ formatMoney(invoice.balance, invoice.currency) }}</p>
                <p class="mt-2 text-small" :class="days < 0 ? 'font-medium text-danger' : 'text-ink-3'">
                    {{ days < 0 ? `${Math.abs(days)} days overdue` : days === 0 ? 'Due today' : `Due ${formatDate(invoice.due_date)}` }}
                </p>
                <div class="mt-5 rounded-lg bg-subtle p-3 text-small text-ink-2">
                    <p class="flex items-start gap-2"><Info class="mt-0.5 size-4 shrink-0 text-ink-3" />Pay by bank transfer using the details in the payment terms, quoting {{ invoice.number }}. {{ from.name }} will mark it paid once it arrives.</p>
                </div>
            </Card>
            <Card v-else>
                <p class="text-body font-medium text-ink">This invoice was cancelled</p>
                <p class="mt-1 text-small text-ink-3">Nothing is owed on it.</p>
            </Card>
        </aside>
    </div>
</template>
