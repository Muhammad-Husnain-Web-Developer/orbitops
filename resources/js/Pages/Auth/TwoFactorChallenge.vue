<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { nextTick, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';

const recovery = ref(false);
const input = ref(null);

const form = useForm({ code: '', recovery_code: '' });

async function toggle() {
    recovery.value = !recovery.value;
    form.clearErrors();
    form.reset();
    await nextTick();
    input.value?.focus();
}

function submit() {
    form.transform((data) => (recovery.value ? { recovery_code: data.recovery_code } : { code: data.code })).post(route('two-factor.login.store'));
}
</script>

<template>
    <Head title="Two-factor authentication" />
    <span class="flex size-12 items-center justify-center rounded-2xl bg-accent/10"><ShieldCheck class="size-6 text-accent-text" /></span>
    <h1 class="mt-6 text-h2 text-ink">Two-factor authentication</h1>
    <p class="mt-2 text-body text-ink-3">
        {{ recovery ? 'Enter one of your emergency recovery codes.' : 'Enter the 6-digit code from your authenticator app.' }}
    </p>
    <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
        <Field v-if="!recovery" label="Authentication code" :error="form.errors.code" v-slot="{ id, invalid }">
            <Input :id="id" ref="input" v-model="form.code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus :invalid="invalid" class="[&_input]:text-center [&_input]:font-mono [&_input]:text-[1.25rem] [&_input]:tracking-[0.5em]" size="lg" />
        </Field>
        <Field v-else label="Recovery code" :error="form.errors.recovery_code" v-slot="{ id, invalid }">
            <Input :id="id" ref="input" v-model="form.recovery_code" autocomplete="one-time-code" :invalid="invalid" placeholder="xxxxxxxxxx-xxxxxxxxxx" />
        </Field>
        <Button type="submit" size="lg" block :loading="form.processing">Verify</Button>
    </form>
    <button type="button" class="mt-6 text-body font-medium text-accent-text hover:underline" @click="toggle">
        {{ recovery ? 'Use an authentication code' : 'Use a recovery code instead' }}
    </button>
</template>
