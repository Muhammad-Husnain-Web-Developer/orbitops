<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import ColorPicker from '@/Components/UI/ColorPicker.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import MemberPicker from '@/Components/UI/MemberPicker.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { useEnums } from '@/composables/useEnums';
import { useLookups } from '@/composables/useLookups';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    project: { type: Object, default: null },
    defaults: { type: Object, default: () => ({}) },
});

const page = usePage();
const { options } = useEnums();
const { lookups, load, invalidate } = useLookups();

const blank = () => ({
    name: '',
    client_id: '',
    code: '',
    description: '',
    status: 'planning',
    priority: 'medium',
    color: 'violet',
    billing_type: 'fixed',
    budget: '',
    hourly_rate: '',
    start_date: '',
    due_date: '',
    owner_id: page.props.auth.user.id,
    member_ids: [],
});

const form = useForm(blank());
const codeTouched = computed(() => Boolean(props.project));

watch(open, (value) => {
    if (!value) return;
    load(page.props.workspace.id);
    form.clearErrors();

    if (props.project) {
        form.defaults({
            ...blank(),
            ...Object.fromEntries(Object.keys(blank()).map((key) => [key, props.project[key] ?? blank()[key]])),
            client_id: props.project.client_id ?? '',
            budget: props.project.budget || '',
            hourly_rate: props.project.hourly_rate ?? '',
            member_ids: (props.project.members ?? []).map((member) => member.id),
        });
    } else {
        form.defaults({ ...blank(), ...props.defaults });
    }

    form.reset();
});

// Suggest a short project key from the name, e.g. "Website Redesign" → "WR".
watch(
    () => form.name,
    (name) => {
        if (codeTouched.value || !name) return;
        const words = name.trim().split(/\s+/);
        form.code = (words.length > 1 ? words.map((word) => word[0]).join('') : name.slice(0, 4)).replace(/[^a-z]/gi, '').slice(0, 5).toUpperCase();
    },
);

const clientOptions = computed(() => lookups.clients.map((client) => ({ value: client.id, label: client.name })));
const memberOptions = computed(() => lookups.members.map((member) => ({ value: member.id, label: member.name })));
const billingTypes = [
    { value: 'fixed', label: 'Fixed price' },
    { value: 'hourly', label: 'Hourly' },
    { value: 'retainer', label: 'Retainer' },
];
const priorities = options('taskPriority');

function submit() {
    const config = { preserveScroll: true, onSuccess: () => ((open.value = false), invalidate()) };
    props.project ? form.put(route('projects.update', props.project.id), config) : form.post(route('projects.store'), config);
}
</script>

<template>
    <Modal v-model:open="open" :title="project ? 'Edit project' : 'New project'" :description="project ? null : 'Set the client, budget and team. Milestones and tasks come next.'" size="lg">
        <form id="project-form" class="grid gap-4 sm:grid-cols-6" novalidate @submit.prevent="submit">
            <Field label="Project name" :error="form.errors.name" required class="sm:col-span-4" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.name" autofocus :invalid="invalid" placeholder="Website Redesign" />
            </Field>
            <Field label="Key" :error="form.errors.code" hint="Used in task IDs" class="sm:col-span-2" v-slot="{ id, invalid, describedby }">
                <Input :id="id" v-model="form.code" maxlength="10" :invalid="invalid" :aria-describedby="describedby" class="[&_input]:font-mono [&_input]:uppercase" />
            </Field>
            <Field label="Client" :error="form.errors.client_id" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Skeleton v-if="!lookups.loaded" class="h-9" />
                <Select v-else :id="id" v-model="form.client_id" :options="clientOptions" placeholder="Internal project" :invalid="invalid" />
            </Field>
            <Field label="Project lead" :error="form.errors.owner_id" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Skeleton v-if="!lookups.loaded" class="h-9" />
                <Select v-else :id="id" v-model="form.owner_id" :options="memberOptions" :invalid="invalid" />
            </Field>
            <Field label="Description" :error="form.errors.description" class="sm:col-span-6" v-slot="{ id, invalid }">
                <Textarea :id="id" v-model="form.description" :rows="3" :invalid="invalid" placeholder="What does success look like?" />
            </Field>
            <Field label="Status" :error="form.errors.status" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.status" :options="options('projectStatus')" :invalid="invalid" />
            </Field>
            <Field label="Priority" :error="form.errors.priority" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.priority" :options="priorities" :invalid="invalid" />
            </Field>
            <Field label="Billing" :error="form.errors.billing_type" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.billing_type" :options="billingTypes" :invalid="invalid" />
            </Field>
            <Field label="Budget" :error="form.errors.budget" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.budget" type="number" min="0" step="100" prefix="$" :invalid="invalid" placeholder="0" />
            </Field>
            <Field v-if="form.billing_type === 'hourly'" label="Hourly rate" :error="form.errors.hourly_rate" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.hourly_rate" type="number" min="0" step="5" prefix="$" suffix="/ hr" :invalid="invalid" />
            </Field>
            <div v-else class="hidden sm:col-span-3 sm:block" />
            <Field label="Start date" :error="form.errors.start_date" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.start_date" type="date" :invalid="invalid" />
            </Field>
            <Field label="Due date" :error="form.errors.due_date" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.due_date" type="date" :invalid="invalid" />
            </Field>
            <div class="sm:col-span-6">
                <ColorPicker v-model="form.color" />
            </div>
            <div class="sm:col-span-6">
                <Skeleton v-if="!lookups.loaded" class="h-8 w-2/3" />
                <MemberPicker v-else v-model="form.member_ids" :members="lookups.members" label="Team members" />
                <p v-if="form.errors.member_ids" class="mt-1.5 text-small text-danger">{{ form.errors.member_ids }}</p>
            </div>
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="project-form" :loading="form.processing">{{ project ? 'Save changes' : 'Create project' }}</Button>
        </template>
    </Modal>
</template>
