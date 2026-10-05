<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Banknote, Copy, Mail, MoreHorizontal, Pencil, Printer, Send, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import InvoiceDocument from '@/Components/Invoices/InvoiceDocument.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Select from '@/Components/UI/Select.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { confirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { toast } from '@/composables/useToast';
import { daysUntil, formatDate, formatMoney, toDateInput } from '@/lib/format';

const props = defineProps({
    invoice: { type: Object, required: true },
    from: { type: Object, required: true },
    timeline: { type: Array, required: true },
    payments: { type: Array, required: true },
    paymentMethods: { type: Array, required: true },
    can: { type: Object, required: true },
});

const { can: canDo } = usePermissions();
const money = (value) => formatMoney(value, props.invoice.currency);

const outstanding = computed(() => ['sent', 'overdue'].includes(props.invoice.status));
const paidPercent = computed(() => (props.invoice.total > 0 ? Math.min(100, (props.invoice.amount_paid / props.invoice.total) * 100) : 0));

const dueText = computed(() => {
    if (props.invoice.status === 'paid') return `Paid ${formatDate(props.invoice.paid_at)}`;
    if (props.invoice.status === 'cancelled') return 'Cancelled';
    if (props.invoice.status === 'draft') return `Due ${formatDate(props.invoice.due_date)} once sent`;

    const days = daysUntil(props.invoice.due_date);
    if (days < 0) return `${Math.abs(days)} ${Math.abs(days) === 1 ? 'day' : 'days'} overdue`;
    if (days === 0) return 'Due today';

    return `Due in ${days} ${days === 1 ? 'day' : 'days'}`;
});

const onError = (errors) => toast.error(Object.values(errors)[0] ?? 'Something went wrong');

async function send() {
    const reminder = props.invoice.status !== 'draft';

    if (!props.invoice.client?.email) {
        toast.error(`Add a billing email to ${props.invoice.client?.name} first`, { description: 'You can edit the client from their page.' });
        return;
    }

    const ok = await confirm({
        title: reminder ? `Send a reminder for ${props.invoice.number}?` : `Send ${props.invoice.number}?`,
        description: `${props.invoice.client.contact_name || props.invoice.client.name} will get an email at ${props.invoice.client.email} with a link to view ${reminder ? 'and pay ' : ''}${money(props.invoice.balance)}.`,
        confirmLabel: reminder ? 'Send reminder' : 'Send invoice',
        tone: 'accent',
    });

    if (ok) router.post(route('invoices.send', props.invoice.id), {}, { preserveScroll: true, onError });
}

async function cancelInvoice() {
    if (await confirm({ title: `Cancel ${props.invoice.number}?`, description: 'The invoice stops counting as outstanding. You can duplicate it later if you need to bill again.', confirmLabel: 'Cancel invoice', cancelLabel: 'Keep invoice' })) {
        router.post(route('invoices.cancel', props.invoice.id), {}, { preserveScroll: true, onError });
    }
}

async function destroy() {
    if (await confirm({ title: `Delete ${props.invoice.number}?`, description: 'This removes the invoice permanently.', confirmLabel: 'Delete invoice' })) {
        router.delete(route('invoices.destroy', props.invoice.id), { onError });
    }
}

function duplicate() {
    router.post(route('invoices.duplicate', props.invoice.id), {}, { onError });
}

// Record payment
const paying = ref(false);
const payment = useForm({ amount: '', paid_on: '', method: 'bank_transfer', reference: '' });

function openPayment() {
    payment.defaults({ amount: props.invoice.balance, paid_on: toDateInput(), method: 'bank_transfer', reference: '' });
    payment.reset();
    payment.clearErrors();
    paying.value = true;
}

function recordPayment() {
    payment.post(route('invoices.payments.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => (paying.value = false),
    });
}

const print = () => window.print();
</script>

<template>
    <Head :title="`${invoice.number} · ${invoice.client?.name}`" />

    <div class="print:hidden">
        <Link :href="route('invoices.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-3.5" />Invoices</Link>

        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-mono text-h2 text-ink sm:text-[1.75rem]">{{ invoice.number }}</h1>
                    <StatusBadge group="invoiceStatus" :value="invoice.status" />
                </div>
                <p class="mt-1 text-body text-ink-3">
                    <Link :href="route('clients.show', invoice.client_id)" class="hover:text-ink hover:underline">{{ invoice.client?.name }}</Link>
                    <template v-if="invoice.project"> · {{ invoice.project.name }}</template>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <template v-if="can.manage">
                    <Button v-if="invoice.status === 'draft'" :icon="Send" @click="send">Send invoice</Button>
                    <Button v-if="outstanding" :icon="Banknote" @click="openPayment">Record payment</Button>
                    <Button v-if="outstanding" variant="secondary" :icon="Mail" @click="send">Send reminder</Button>
                    <Button v-if="can.update" variant="secondary" :icon="Pencil" :href="route('invoices.edit', invoice.id)">Edit</Button>
                </template>
                <Button variant="secondary" :icon="Printer" @click="print">Print / PDF</Button>
                <Dropdown v-if="can.manage || can.delete" align="end" width="w-52" label="More invoice actions">
                    <template #trigger="{ attrs }"><Button v-bind="attrs" variant="secondary" square :icon="MoreHorizontal" aria-label="More invoice actions" /></template>
                    <DropdownItem v-if="canDo('invoices.manage')" :icon="Copy" @select="duplicate">Duplicate as draft</DropdownItem>
                    <DropdownItem v-if="can.manage && !['paid', 'cancelled'].includes(invoice.status)" :icon="Ban" @select="cancelInvoice">Cancel invoice</DropdownItem>
                    <template v-if="can.delete">
                        <DropdownSeparator />
                        <DropdownItem :icon="Trash2" danger @select="destroy">Delete</DropdownItem>
                    </template>
                </Dropdown>
            </div>
        </header>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px] print:block">
        <InvoiceDocument :invoice="invoice" :from="from" class="min-w-0" />

        <aside class="space-y-6 print:hidden" aria-label="Invoice details">
            <Card>
                <p class="text-small text-ink-3">{{ invoice.status === 'paid' ? 'Total paid' : 'Balance due' }}</p>
                <p class="mt-1 text-[2rem] leading-none font-semibold tracking-tight text-ink tabular">{{ money(invoice.status === 'paid' ? invoice.total : invoice.balance) }}</p>
                <p class="mt-2 text-small" :class="invoice.status === 'overdue' ? 'font-medium text-danger' : 'text-ink-3'">{{ dueText }}</p>
                <div v-if="outstanding || invoice.status === 'paid'" class="mt-5">
                    <ProgressBar :value="paidPercent" :tone="invoice.status === 'paid' ? 'success' : 'accent'" size="sm" label="Amount paid" />
                    <p class="mt-2 flex justify-between text-caption text-ink-3 tabular"><span>{{ money(invoice.amount_paid) }} paid</span><span>of {{ money(invoice.total) }}</span></p>
                </div>
                <dl class="mt-5 space-y-2 border-t border-line pt-4 text-small">
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Sent</dt><dd class="text-ink">{{ invoice.sent_at ? formatDate(invoice.sent_at) : 'Not yet' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Recipient</dt><dd class="truncate text-ink">{{ invoice.client?.email ?? 'No billing email' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-3">Currency</dt><dd class="text-ink">{{ invoice.currency }}</dd></div>
                </dl>
            </Card>

            <Card v-if="payments.length" title="Payments">
                <ul class="divide-y divide-line">
                    <li v-for="item in payments" :key="item.id" class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-small font-medium text-ink">{{ item.method }}</p>
                            <p class="truncate text-caption text-ink-3">{{ formatDate(item.paid_on) }}<template v-if="item.reference"> · {{ item.reference }}</template></p>
                        </div>
                        <span class="text-small font-medium text-success tabular">{{ money(item.amount) }}</span>
                    </li>
                </ul>
            </Card>

            <Card title="History">
                <ActivityFeed v-if="timeline.length" :items="timeline" />
                <p v-else class="text-small text-ink-3">No activity yet.</p>
            </Card>
        </aside>
    </div>

    <Modal v-model:open="paying" title="Record a payment" :description="`${invoice.number} · ${money(invoice.balance)} due`">
        <form id="payment-form" class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="recordPayment">
            <Field label="Amount" :error="payment.errors.amount" required v-slot="{ id, invalid }">
                <Input :id="id" v-model="payment.amount" type="number" min="0.01" step="0.01" :max="invoice.balance" :suffix="invoice.currency" :invalid="invalid" />
            </Field>
            <Field label="Paid on" :error="payment.errors.paid_on" required v-slot="{ id, invalid }">
                <Input :id="id" v-model="payment.paid_on" type="date" :max="toDateInput()" :invalid="invalid" />
            </Field>
            <Field label="Method" :error="payment.errors.method" v-slot="{ id, invalid }">
                <Select :id="id" v-model="payment.method" :options="paymentMethods" :invalid="invalid" />
            </Field>
            <Field label="Reference" :error="payment.errors.reference" hint="Optional" v-slot="{ id, invalid }">
                <Input :id="id" v-model="payment.reference" placeholder="e.g. TRX-20491" :invalid="invalid" />
            </Field>
            <p v-if="Number(payment.amount) > 0 && Number(payment.amount) < invoice.balance" class="text-small text-ink-3 sm:col-span-2">
                A partial payment leaves {{ money(invoice.balance - Number(payment.amount)) }} outstanding.
            </p>
            <p v-else-if="Number(payment.amount) === invoice.balance" class="text-small text-success sm:col-span-2">This settles the invoice in full and marks it paid.</p>
        </form>
        <template #footer>
            <Button variant="secondary" @click="paying = false">Cancel</Button>
            <Button type="submit" form="payment-form" :loading="payment.processing">Record payment</Button>
        </template>
    </Modal>
</template>
