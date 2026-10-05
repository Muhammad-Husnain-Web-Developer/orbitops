<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';

const form = useForm({ password: '' });

function submit() {
    form.post(route('password.confirm.store'), { onFinish: () => form.reset() });
}
</script>

<template>
    <Head title="Confirm your password" />
    <span class="flex size-12 items-center justify-center rounded-2xl bg-accent/10"><LockKeyhole class="size-6 text-accent-text" /></span>
    <h1 class="mt-6 text-h2 text-ink">Confirm it's you</h1>
    <p class="mt-2 text-body text-ink-3">This is a secure area. Please confirm your password to continue.</p>
    <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
        <Field label="Password" :error="form.errors.password" v-slot="{ id, invalid }">
            <PasswordInput :id="id" v-model="form.password" autocomplete="current-password" autofocus required :invalid="invalid" />
        </Field>
        <Button type="submit" size="lg" block :loading="form.processing">Confirm</Button>
    </form>
</template>
