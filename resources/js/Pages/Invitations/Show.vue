<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert, MailPlus } from '@lucide/vue';
import Alert from '@/Components/UI/Alert.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    token: { type: String, required: true },
    invitation: { type: Object, default: null },
    valid: { type: Boolean, default: false },
    emailMatches: { type: Boolean, default: false },
});

const page = usePage();
const form = useForm({});

function accept() {
    form.post(route('invitations.accept', props.token));
}
</script>

<template>
    <Head title="Workspace invitation" />
    <template v-if="!invitation || !valid">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-warning/12"><CircleAlert class="size-6 text-warning" /></span>
        <h1 class="mt-6 text-h2 text-ink">This invitation has expired</h1>
        <p class="mt-2 text-body text-ink-3">Invitations are valid for 7 days and can only be used once. Ask the person who invited you to send a new one.</p>
        <Button :href="route('home')" variant="secondary" size="lg" block class="mt-8">Go to OrbitOps</Button>
    </template>
    <template v-else>
        <span class="flex size-12 items-center justify-center rounded-2xl bg-accent/10"><MailPlus class="size-6 text-accent-text" /></span>
        <h1 class="mt-6 text-h2 text-ink">Join {{ invitation.workspace.name }}</h1>
        <p class="mt-2 text-body text-ink-3">
            <span class="font-medium text-ink">{{ invitation.inviter?.name ?? 'A teammate' }}</span> invited you to collaborate as
            <span class="font-medium text-ink">{{ invitation.role }}</span>.
        </p>

        <div class="mt-6 flex items-center gap-3 rounded-xl border border-line bg-surface p-4 shadow-card">
            <Avatar :name="invitation.workspace.name" square size="lg" decorative />
            <div class="min-w-0">
                <p class="text-body font-semibold text-ink">{{ invitation.workspace.name }}</p>
                <p class="text-small text-ink-3">{{ invitation.workspace.industry ?? 'Workspace' }} · expires {{ formatRelative(invitation.expires_at) }}</p>
            </div>
        </div>

        <template v-if="page.props.auth.user">
            <Alert v-if="!emailMatches" tone="warning" class="mt-6" title="Different account">
                This invitation was sent to {{ invitation.email }}, but you are signed in as {{ page.props.auth.user.email }}.
            </Alert>
            <p v-if="form.errors.invitation" class="mt-4 text-small text-danger" role="alert">{{ form.errors.invitation }}</p>
            <Button v-if="emailMatches" size="lg" block class="mt-8" :loading="form.processing" @click="accept">Accept invitation</Button>
            <Button v-else :href="route('logout')" method="post" variant="secondary" size="lg" block class="mt-8">Log out and switch account</Button>
        </template>
        <template v-else>
            <div class="mt-8 space-y-2.5">
                <Button :href="route('register')" size="lg" block>Create an account</Button>
                <Button :href="route('login')" variant="secondary" size="lg" block>I already have an account</Button>
            </div>
            <p class="mt-4 text-center text-small text-ink-3">Use <span class="font-medium text-ink-2">{{ invitation.email }}</span> to join.</p>
        </template>
    </template>
    <p class="mt-8 text-center text-small text-ink-3"><Link :href="route('home')" class="hover:text-ink">What is OrbitOps?</Link></p>
</template>
