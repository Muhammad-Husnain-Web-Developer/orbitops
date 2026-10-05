<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import { useEnums } from '@/composables/useEnums';
import { useLookups } from '@/composables/useLookups';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    client: { type: Object, default: null },
});

const { options } = useEnums();
const { invalidate } = useLookups();

const blank = { name: '', industry: '', status: 'active', contact_name: '', email: '', phone: '', website: '', city: '', country: '', currency: 'USD' };
const form = useForm({ ...blank });

watch(open, (value) => {
    if (!value) return;
    form.clearErrors();
    form.defaults(props.client ? Object.fromEntries(Object.keys(blank).map((key) => [key, props.client[key] ?? blank[key]])) : { ...blank });
    form.reset();
});

function submit() {
    const config = { preserveScroll: true, onSuccess: () => ((open.value = false), invalidate()) };
    props.client ? form.put(route('clients.update', props.client.id), config) : form.post(route('clients.store'), config);
}
</script>

<template>
    <Modal v-model:open="open" :title="client ? 'Edit client' : 'New client'" :description="client ? null : 'Add a company you work with. You can invite them to the portal later.'" size="lg">
        <form id="client-form" class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="submit">
            <Field label="Company name" :error="form.errors.name" required class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.name" autofocus :invalid="invalid" placeholder="Northstar Media" />
            </Field>
            <Field label="Industry" :error="form.errors.industry" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.industry" :invalid="invalid" placeholder="Media & Publishing" />
            </Field>
            <Field label="Status" :error="form.errors.status" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.status" :options="options('clientStatus')" :invalid="invalid" />
            </Field>
            <Field label="Primary contact" :error="form.errors.contact_name" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.contact_name" :invalid="invalid" autocomplete="off" placeholder="Hannah Brooks" />
            </Field>
            <Field label="Email" :error="form.errors.email" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.email" type="email" :invalid="invalid" autocomplete="off" placeholder="hannah@northstar.com" />
            </Field>
            <Field label="Phone" :error="form.errors.phone" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.phone" type="tel" :invalid="invalid" autocomplete="off" />
            </Field>
            <Field label="Website" :error="form.errors.website" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.website" type="url" :invalid="invalid" placeholder="https://" />
            </Field>
            <Field label="City" :error="form.errors.city" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.city" :invalid="invalid" />
            </Field>
            <Field label="Country" :error="form.errors.country" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.country" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="client-form" :loading="form.processing">{{ client ? 'Save changes' : 'Create client' }}</Button>
        </template>
    </Modal>
</template>
