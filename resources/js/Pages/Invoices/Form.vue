<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Clock, Plus, Send, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { toast } from '@/composables/useToast';
import { api } from '@/lib/api';
import { formatDate, formatMoney, toDate, toDateInput } from '@/lib/format';

const props = defineProps({
    invoice: { type: Object, default: null },
    defaults: { type: Object, default: () => ({}) },
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    currencies: { type: Array, required: true },
});

const editing = computed(() => Boolean(props.invoice));
const status = computed(() => props.invoice?.status ?? 'draft');
let uid = 0;
const line = (item = {}) => ({ key: ++uid, description: item.description ?? '', quantity: item.quantity ?? 1, unit_price: item.unit_price ?? '' });

const source = props.invoice ?? props.defaults;
const form = useForm({
    client_id: source.client_id ?? '',
    project_id: source.project_id ?? '',
    issue_date: source.issue_date,
    due_date: source.due_date,
    currency: source.currency ?? 'USD',
    tax_rate: source.tax_rate ?? 0,
    discount_type: source.discount_type ?? 'fixed',
    discount_value: source.discount_value || '',
    notes: source.notes ?? '',
    terms: source.terms ?? '',
    items: props.invoice?.items?.length ? props.invoice.items.map(line) : [line()],
    send: false,
});

const client = computed(() => props.clients.find((item) => item.id === Number(form.client_id)) ?? null);
const project = computed(() => props.projects.find((item) => item.id === Number(form.project_id)) ?? null);
const clientOptions = computed(() => props.clients.map((item) => ({ value: item.id, label: item.name })));
const projectOptions = computed(() => props.projects.filter((item) => item.client_id === Number(form.client_id)).map((item) => ({ value: item.id, label: item.name })));
const currencyOptions = computed(() => props.currencies.map((code) => ({ value: code, label: code })));

function chooseClient(id) {
    form.project_id = '';
    const chosen = props.clients.find((item) => item.id === Number(id));
    if (chosen) form.currency = chosen.currency;
}

// Payment terms
const terms = [
    { days: 0, label: 'On receipt' },
    { days: 7, label: 'Net 7' },
    { days: 14, label: 'Net 14' },
    { days: 30, label: 'Net 30' },
];

const termDays = computed(() => Math.round((toDate(`${form.due_date}T00:00:00`) - toDate(`${form.issue_date}T00:00:00`)) / 86400000));

function setTerms(days) {
    const due = toDate(`${form.issue_date}T00:00:00`);
    due.setDate(due.getDate() + days);
    form.due_date = toDateInput(due);
}

// Line items
const itemsList = ref(null);

async function addLine(item) {
    form.items.push(line(item));
    await nextTick();
    itemsList.value?.querySelector('li:last-child input')?.focus();
}

function removeLine(index) {
    form.items.splice(index, 1);
    if (!form.items.length) form.items.push(line());
}

const amount = (item) => Math.round((Number(item.quantity) || 0) * (Number(item.unit_price) || 0) * 100) / 100;
const round = (value) => Math.round(value * 100) / 100;

const totals = computed(() => {
    const subtotal = round(form.items.reduce((sum, item) => sum + amount(item), 0));
    const value = Number(form.discount_value) || 0;
    const discount = form.discount_type === 'percent' ? round((subtotal * Math.min(value, 100)) / 100) : Math.min(value, subtotal);
    const tax = round(((subtotal - discount) * (Number(form.tax_rate) || 0)) / 100);

    return { subtotal, discount, tax, total: round(subtotal - discount + tax) };
});

const money = (value) => formatMoney(value, form.currency);
const itemError = (index, field) => form.errors[`items.${index}.${field}`];

// Billable time import
const importing = ref(false);

async function addBillableTime() {
    importing.value = true;

    try {
        const result = await api.get(route('invoices.billable-time'), { params: { project: form.project_id, except: props.invoice?.id } });

        if (!result.lines.length) {
            toast(`No unbilled time on ${project.value.name}`, { type: 'info', description: `Nothing billable has been tracked since ${formatDate(result.from)}.` });
            return;
        }

        // Replace a single untouched blank line instead of leaving it above the import.
        if (form.items.length === 1 && !form.items[0].description && !form.items[0].unit_price) form.items = [];
        result.lines.forEach((item) => form.items.push(line(item)));
        toast.success(`Added ${result.hours}h of billable time`, { description: `Tracked on ${project.value.name} since ${formatDate(result.from)}.` });
    } catch {
        toast.error('Could not load tracked time');
    } finally {
        importing.value = false;
    }
}

function submit(send = false) {
    form.send = send;
    form
        .transform((data) => ({
            ...data,
            items: data.items.filter((item) => item.description || Number(item.unit_price)).map(({ description, quantity, unit_price }) => ({ description, quantity, unit_price })),
        }))
        [editing.value ? 'put' : 'post'](editing.value ? route('invoices.update', props.invoice.id) : route('invoices.store'), {
            onError: () => nextTick(() => document.querySelector('[aria-invalid="true"], [role="alert"]')?.scrollIntoView({ block: 'center', behavior: 'smooth' })),
        });
}

// Warn before closing the tab with unsaved changes.
const beforeUnload = (event) => {
    if (form.isDirty && !form.processing) event.preventDefault();
};
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));
</script>

<template>
    <Head :title="editing ? `Edit ${invoice.number}` : 'New invoice'" />

    <Link :href="editing ? route('invoices.show', invoice.id) : route('invoices.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink">
        <ArrowLeft class="size-3.5" />{{ editing ? invoice.number : 'Invoices' }}
    </Link>

    <header class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">{{ editing ? 'Edit invoice' : 'New invoice' }}</h1>
        <span class="rounded-md border border-line bg-surface px-2 py-0.5 font-mono text-small text-ink-2">{{ editing ? invoice.number : defaults.number }}</span>
    </header>

    <form class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]" novalidate @submit.prevent="submit(false)">
        <div class="min-w-0 space-y-6">
            <Card title="Bill to">
                <div class="grid gap-4 sm:grid-cols-6">
                    <Field label="Client" :error="form.errors.client_id" required class="sm:col-span-3" v-slot="{ id, invalid }">
                        <Select :id="id" v-model="form.client_id" :options="clientOptions" placeholder="Choose a client…" :invalid="invalid" @update:model-value="chooseClient" />
                    </Field>
                    <Field label="Project" :error="form.errors.project_id" hint="Optional" class="sm:col-span-2" v-slot="{ id, invalid }">
                        <Select :id="id" v-model="form.project_id" :options="projectOptions" :placeholder="form.client_id ? (projectOptions.length ? 'No project' : 'No projects') : 'Choose a client first'" :disabled="!form.client_id" :invalid="invalid" />
                    </Field>
                    <Field label="Currency" :error="form.errors.currency" class="sm:col-span-1" v-slot="{ id, invalid }">
                        <Select :id="id" v-model="form.currency" :options="currencyOptions" :invalid="invalid" />
                    </Field>
                </div>
                <p v-if="client && !client.email" class="mt-3 flex items-center gap-2 text-small text-warning"><AlertTriangle class="size-4 shrink-0" />{{ client.name }} has no billing email, so this invoice can be saved but not sent.</p>
            </Card>

            <Card title="Dates">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Issue date" :error="form.errors.issue_date" required v-slot="{ id, invalid }">
                        <Input :id="id" v-model="form.issue_date" type="date" :invalid="invalid" />
                    </Field>
                    <Field label="Due date" :error="form.errors.due_date" required v-slot="{ id, invalid }">
                        <Input :id="id" v-model="form.due_date" type="date" :min="form.issue_date" :invalid="invalid" />
                    </Field>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2" role="group" aria-label="Payment terms">
                    <span class="text-caption text-ink-3">Terms</span>
                    <button
                        v-for="term in terms"
                        :key="term.days"
                        type="button"
                        class="rounded-full border px-2.5 py-1 text-caption font-medium transition-colors"
                        :class="termDays === term.days ? 'border-accent/40 bg-accent/10 text-accent-text' : 'border-line text-ink-2 hover:border-line-strong hover:text-ink'"
                        :aria-pressed="termDays === term.days"
                        @click="setTerms(term.days)"
                    >
                        {{ term.label }}
                    </button>
                </div>
            </Card>

            <Card title="Line items" :padded="false">
                <template #actions>
                    <Button v-if="project" variant="soft" size="sm" :icon="Clock" :loading="importing" @click="addBillableTime">Add billable hours</Button>
                </template>
                <div class="hidden grid-cols-[minmax(0,1fr)_88px_120px_112px_36px] gap-3 border-y border-line bg-canvas/40 px-5 py-2 text-caption font-medium text-ink-3 sm:grid">
                    <span>Description</span><span class="text-right">Qty</span><span class="text-right">Rate</span><span class="text-right">Amount</span><span class="sr-only">Remove</span>
                </div>
                <ul ref="itemsList" class="divide-y divide-line border-t border-line sm:border-t-0">
                    <li v-for="(item, index) in form.items" :key="item.key" class="grid grid-cols-[1fr_1fr_auto] gap-x-3 gap-y-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_88px_120px_112px_36px] sm:items-start sm:px-5">
                        <div class="col-span-3 sm:col-span-1">
                            <label :for="`item-${item.key}-description`" class="sr-only">Description for line {{ index + 1 }}</label>
                            <Input :id="`item-${item.key}-description`" v-model="item.description" placeholder="e.g. Homepage design" :invalid="Boolean(itemError(index, 'description'))" />
                            <p v-if="itemError(index, 'description')" class="mt-1 text-caption text-danger">{{ itemError(index, 'description') }}</p>
                        </div>
                        <div>
                            <label :for="`item-${item.key}-qty`" class="mb-1 block text-caption text-ink-3 sm:sr-only">Qty</label>
                            <Input :id="`item-${item.key}-qty`" v-model="item.quantity" type="number" min="0" step="0.25" inputmode="decimal" class="[&_input]:text-right" :invalid="Boolean(itemError(index, 'quantity'))" />
                            <p v-if="itemError(index, 'quantity')" class="mt-1 text-caption text-danger">{{ itemError(index, 'quantity') }}</p>
                        </div>
                        <div>
                            <label :for="`item-${item.key}-rate`" class="mb-1 block text-caption text-ink-3 sm:sr-only">Rate</label>
                            <Input :id="`item-${item.key}-rate`" v-model="item.unit_price" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" class="[&_input]:text-right" :invalid="Boolean(itemError(index, 'unit_price'))" />
                        </div>
                        <div class="flex items-end justify-end sm:h-9 sm:items-center">
                            <span class="text-body font-medium text-ink tabular">{{ money(amount(item)) }}</span>
                        </div>
                        <div class="col-span-3 flex justify-end sm:col-span-1 sm:block">
                            <button type="button" class="inline-flex h-9 items-center gap-1 rounded-lg px-2 text-small text-ink-3 transition-colors hover:bg-danger/10 hover:text-danger sm:size-9 sm:justify-center sm:px-0" :aria-label="`Remove line ${index + 1}`" @click="removeLine(index)">
                                <X class="size-4" /><span class="sm:sr-only">Remove</span>
                            </button>
                        </div>
                    </li>
                </ul>
                <div class="border-t border-line px-4 py-3 sm:px-5">
                    <Button variant="ghost" size="sm" :icon="Plus" @click="addLine()">Add line</Button>
                    <p v-if="form.errors.items" class="mt-2 text-small text-danger" role="alert">{{ form.errors.items }}</p>
                </div>
            </Card>

            <Card title="Notes & terms">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Notes to client" :error="form.errors.notes" hint="Shown on the invoice" v-slot="{ id, invalid }">
                        <Textarea :id="id" v-model="form.notes" :rows="4" placeholder="Thanks for a great quarter!" :invalid="invalid" />
                    </Field>
                    <Field label="Payment terms" :error="form.errors.terms" v-slot="{ id, invalid }">
                        <Textarea :id="id" v-model="form.terms" :rows="4" placeholder="Bank details, late fees…" :invalid="invalid" />
                    </Field>
                </div>
            </Card>
        </div>

        <!-- Summary -->
        <aside class="lg:sticky lg:top-20" aria-label="Invoice summary">
            <Card title="Summary">
                <dl class="space-y-3 text-small">
                    <div class="flex justify-between text-ink-2"><dt>Subtotal</dt><dd class="tabular">{{ money(totals.subtotal) }}</dd></div>
                    <div>
                        <dt class="mb-1.5 flex items-center justify-between text-ink-2">
                            <span>Discount</span>
                            <SegmentedControl v-model="form.discount_type" :options="[{ value: 'fixed', label: form.currency }, { value: 'percent', label: '%' }]" label="Discount type" size="sm" />
                        </dt>
                        <dd class="flex items-center gap-3">
                            <Input v-model="form.discount_value" type="number" min="0" step="0.01" placeholder="0" size="sm" aria-label="Discount" :invalid="Boolean(form.errors.discount_value)" class="flex-1" />
                            <span class="w-24 text-right text-ink-2 tabular">−{{ money(totals.discount) }}</span>
                        </dd>
                        <p v-if="form.errors.discount_value" class="mt-1 text-caption text-danger">{{ form.errors.discount_value }}</p>
                    </div>
                    <div>
                        <dt class="mb-1.5 text-ink-2">Tax</dt>
                        <dd class="flex items-center gap-3">
                            <Input v-model="form.tax_rate" type="number" min="0" max="100" step="0.01" suffix="%" size="sm" aria-label="Tax rate" :invalid="Boolean(form.errors.tax_rate)" class="flex-1" />
                            <span class="w-24 text-right text-ink-2 tabular">{{ money(totals.tax) }}</span>
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-line pt-3">
                        <dt class="text-body font-medium text-ink">Total</dt>
                        <dd class="text-h2 text-ink tabular" aria-live="polite">{{ money(totals.total) }}</dd>
                    </div>
                </dl>

                <div class="mt-6 space-y-2">
                    <template v-if="status === 'draft'">
                        <Button type="button" block :icon="Send" :loading="form.processing && form.send" :disabled="form.processing || (client && !client.email)" @click="submit(true)">Save &amp; send</Button>
                        <Button type="submit" variant="secondary" block :loading="form.processing && !form.send" :disabled="form.processing">Save draft</Button>
                    </template>
                    <Button v-else type="submit" block :loading="form.processing" :disabled="form.processing">Save changes</Button>
                </div>
                <p class="mt-3 text-center text-caption text-ink-3">
                    <template v-if="status === 'draft' && client?.email">Sends to {{ client.email }}</template>
                    <template v-else-if="status !== 'draft'">The client keeps the same link to this invoice.</template>
                    <template v-else>Drafts are only visible to your team.</template>
                </p>
            </Card>
        </aside>
    </form>
</template>
