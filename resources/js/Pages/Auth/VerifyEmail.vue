<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { MailOpen } from '@lucide/vue';
import { computed } from 'vue';
import Alert from '@/Components/UI/Alert.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    status: { type: String, default: null },
});

const page = usePage();
const form = useForm({});
const sent = computed(() => props.status === 'verification-link-sent');

function resend() {
    form.post(route('verification.send'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Verify your email" />
    <span class="flex size-12 items-center justify-center rounded-2xl bg-accent/10"><MailOpen class="size-6 text-accent-text" /></span>
    <h1 class="mt-6 text-h2 text-ink">Verify your email</h1>
    <p class="mt-2 text-body text-ink-3">
        We sent a verification link to <span class="font-medium text-ink">{{ page.props.auth.user?.email }}</span>. Click it to activate your workspace.
    </p>
    <Alert v-if="sent" tone="success" class="mt-6">A new verification link is on its way.</Alert>
    <div class="mt-8 space-y-3">
        <Button size="lg" block :loading="form.processing" @click="resend">Resend verification email</Button>
        <Button :href="route('logout')" method="post" variant="ghost" size="lg" block>Log out</Button>
    </div>
    <p class="mt-6 text-small text-ink-3">Wrong address? <Link :href="route('logout')" method="post" as="button" class="font-medium text-accent-text hover:underline">Sign up again</Link> with the right one.</p>
</template>
