<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Choose a new password" />
    <h1 class="text-h2 text-ink">Choose a new password</h1>
    <p class="mt-1.5 text-body text-ink-3">Use at least 8 characters. A passphrase is easiest to remember.</p>
    <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
        <Field label="Email" :error="form.errors.email" v-slot="{ id, invalid }">
            <Input :id="id" v-model="form.email" type="email" autocomplete="username" required readonly :invalid="invalid" />
        </Field>
        <Field label="New password" :error="form.errors.password" v-slot="{ id, invalid, describedby }">
            <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" autofocus required strength :invalid="invalid" :aria-describedby="describedby" />
        </Field>
        <Field label="Confirm password" :error="form.errors.password_confirmation" v-slot="{ id, invalid }">
            <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" required :invalid="invalid" />
        </Field>
        <Button type="submit" size="lg" block :loading="form.processing">Reset password</Button>
    </form>
</template>
