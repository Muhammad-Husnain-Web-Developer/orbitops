<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import ColorPicker from '@/Components/UI/ColorPicker.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';

import Modal from '@/Components/UI/Modal.vue';

const open = defineModel('open', { type: Boolean, default: false });

const form = useForm({ name: '', industry: '', accent: 'blue' });

watch(open, (value) => value && (form.reset(), form.clearErrors()));

function submit() {
    form.post(route('workspaces.store'), { onSuccess: () => (open.value = false) });
}
</script>

<template>
    <Modal v-model:open="open" title="Create workspace" description="A separate space with its own team, clients and data.">
        <form id="workspace-form" class="space-y-4" novalidate @submit.prevent="submit">
            <Field label="Workspace name" :error="form.errors.name" required v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.name" autofocus :invalid="invalid" placeholder="Nova Labs" />
            </Field>
            <Field label="Business type" :error="form.errors.industry" optional v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.industry" :invalid="invalid" placeholder="Software Company" />
            </Field>
            <ColorPicker v-model="form.accent" label="Accent colour" />
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="workspace-form" :loading="form.processing">Create workspace</Button>
        </template>
    </Modal>
</template>
