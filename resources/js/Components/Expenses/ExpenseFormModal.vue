<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { FileText, Paperclip, UploadCloud, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import Switch from '@/Components/UI/Switch.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { useEnums } from '@/composables/useEnums';
import { formatBytes, toDateInput } from '@/lib/format';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    expense: { type: Object, default: null },
    projects: { type: Array, default: () => [] },
});

const page = usePage();
const { options } = useEnums();

const blank = () => ({
    vendor: '',
    description: '',
    amount: '',
    currency: page.props.workspace?.currency ?? 'USD',
    spent_on: toDateInput(),
    category: 'software',
    project_id: '',
    billable: false,
    notes: '',
    receipt: null,
    remove_receipt: false,
});

const form = useForm(blank());
const fileInput = ref(null);
const dragging = ref(false);

watch(open, (value) => {
    if (!value) return;
    form.clearErrors();

    form.defaults(
        props.expense
            ? {
                  ...blank(),
                  vendor: props.expense.vendor ?? '',
                  description: props.expense.description,
                  amount: props.expense.amount,
                  currency: props.expense.currency,
                  spent_on: props.expense.spent_on,
                  category: props.expense.category,
                  project_id: props.expense.project_id ?? '',
                  billable: props.expense.billable,
                  notes: props.expense.notes ?? '',
              }
            : blank(),
    );
    form.reset();
});

const projectOptions = computed(() => props.projects.map((project) => ({ value: project.id, label: project.name })));
const existingReceipt = computed(() => (props.expense?.receipt_name && !form.remove_receipt && !form.receipt ? props.expense.receipt_name : null));

function pick(files) {
    const [file] = files ?? [];
    if (file) {
        form.receipt = file;
        form.remove_receipt = false;
    }
}

function clearReceipt() {
    form.receipt = null;
    if (props.expense?.receipt_name) form.remove_receipt = true;
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    const config = { preserveScroll: true, forceFormData: true, onSuccess: () => (open.value = false) };

    // Files need a multipart POST; Laravel reads the intended method from _method.
    if (props.expense) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('expenses.update', props.expense.id), config);
    } else {
        form.transform((data) => data).post(route('expenses.store'), config);
    }
}
</script>

<template>
    <Modal v-model:open="open" :title="expense ? 'Edit expense' : 'Add expense'" :description="expense ? null : 'Log a cost and attach the receipt. Expenses start as pending until approved.'" size="lg">
        <form id="expense-form" class="grid gap-4 sm:grid-cols-6" novalidate @submit.prevent="submit">
            <Field label="Vendor" :error="form.errors.vendor" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.vendor" placeholder="e.g. Figma" :invalid="invalid" />
            </Field>
            <Field label="Category" :error="form.errors.category" required class="sm:col-span-3" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.category" :options="options('expenseCategory')" :invalid="invalid" />
            </Field>
            <Field label="Description" :error="form.errors.description" required class="sm:col-span-6" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.description" placeholder="What was it for?" :invalid="invalid" />
            </Field>
            <Field label="Amount" :error="form.errors.amount" required class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.amount" type="number" min="0.01" step="0.01" inputmode="decimal" :suffix="form.currency" placeholder="0.00" :invalid="invalid" />
            </Field>
            <Field label="Date" :error="form.errors.spent_on" required class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.spent_on" type="date" :max="toDateInput()" :invalid="invalid" />
            </Field>
            <Field label="Project" :error="form.errors.project_id" hint="Optional" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.project_id" :options="projectOptions" placeholder="Overhead" :invalid="invalid" />
            </Field>

            <div class="sm:col-span-6">
                <p class="mb-1.5 text-label text-ink-2">Receipt</p>
                <div v-if="form.receipt || existingReceipt" class="flex items-center gap-3 rounded-lg border border-line bg-canvas/40 px-3 py-2.5">
                    <FileText class="size-5 shrink-0 text-ink-3" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-small font-medium text-ink">{{ form.receipt?.name ?? existingReceipt }}</p>
                        <p class="text-caption text-ink-3">{{ form.receipt ? formatBytes(form.receipt.size) : 'Attached' }}</p>
                    </div>
                    <Button variant="ghost" size="sm" square :icon="X" aria-label="Remove receipt" @click="clearReceipt" />
                </div>
                <label
                    v-else
                    class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed px-4 py-5 text-center transition-colors has-focus-visible:outline-2 has-focus-visible:outline-accent"
                    :class="dragging ? 'border-accent bg-accent/5' : 'border-line-strong hover:border-ink-3 hover:bg-hover/50'"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
                >
                    <UploadCloud class="size-5 text-ink-3" />
                    <span class="text-small text-ink-2"><span class="font-medium text-accent-text">Upload a receipt</span> or drag it here</span>
                    <span class="text-caption text-ink-3">PDF, JPG, PNG or HEIC up to 10 MB</span>
                    <input ref="fileInput" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.webp,.heic" @change="pick($event.target.files)" />
                </label>
                <p v-if="form.errors.receipt" class="mt-1.5 text-small text-danger" role="alert">{{ form.errors.receipt }}</p>
                <div v-if="form.progress" class="mt-2 flex items-center gap-2 text-caption text-ink-3"><Paperclip class="size-3.5" />Uploading {{ form.progress.percentage }}%</div>
            </div>

            <div class="sm:col-span-6">
                <Switch v-model="form.billable" label="Billable to client" description="Re-bill this cost on the project's next invoice." :disabled="!form.project_id" />
            </div>
            <Field label="Notes" :error="form.errors.notes" class="sm:col-span-6" v-slot="{ id, invalid }">
                <Textarea :id="id" v-model="form.notes" :rows="2" autosize placeholder="Anything the approver should know" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="expense-form" :loading="form.processing">{{ expense ? 'Save changes' : 'Submit expense' }}</Button>
        </template>
    </Modal>
</template>
