<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, MailCheck } from '@lucide/vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({ email: '' });

function submit() {
    form.post(route('password.email'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Reset your password" />
    <template v-if="status">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-success/12"><MailCheck class="size-6 text-success" /></span>
        <h1 class="mt-6 text-h2 text-ink">Check your inbox</h1>
        <p class="mt-2 text-body text-ink-3" role="status">{{ status }} The link expires in 60 minutes.</p>
        <Button class="mt-8" variant="secondary" block :loading="form.processing" @click="submit">Resend the link</Button>
    </template>
    <template v-else>
        <h1 class="text-h2 text-ink">Forgot your password?</h1>
        <p class="mt-1.5 text-body text-ink-3">Enter your email and we will send you a link to choose a new one.</p>
        <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
            <Field label="Email" :error="form.errors.email" v-slot="{ id, invalid, describedby }">
                <Input :id="id" v-model="form.email" type="email" autocomplete="email" autofocus required :invalid="invalid" :aria-describedby="describedby" placeholder="you@company.com" />
            </Field>
            <Button type="submit" size="lg" block :loading="form.processing">Send reset link</Button>
        </form>
    </template>
    <Link :href="route('login')" class="mt-8 inline-flex items-center gap-1.5 text-body font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-4" />Back to log in</Link>
</template>
