<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';

const props = defineProps({
    invitationEmail: { type: String, default: null },
});

const plan = new URLSearchParams(window.location.search).get('plan');
const planLabel = computed(() => ({ starter: 'Starter', growth: 'Growth', scale: 'Scale' })[plan] ?? null);

const form = useForm({
    name: '',
    email: props.invitationEmail ?? '',
    workspace: '',
    plan: planLabel.value ? plan : 'starter',
    password: '',
    password_confirmation: '',
    terms: false,
});

function submit() {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Create your workspace" />
    <p v-if="planLabel" class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2.5 py-1 text-caption font-medium text-accent-text"><Sparkles class="size-3" />{{ planLabel }} plan selected</p>
    <h1 class="text-h2 text-ink">{{ invitationEmail ? 'Join your team' : 'Create your workspace' }}</h1>
    <p class="mt-1.5 text-body text-ink-3">{{ invitationEmail ? 'Create your account to accept the invitation.' : 'Start free — no credit card required.' }}</p>

    <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
        <Field label="Full name" :error="form.errors.name" v-slot="{ id, invalid, describedby }">
            <Input :id="id" v-model="form.name" autocomplete="name" autofocus required :invalid="invalid" :aria-describedby="describedby" placeholder="Alex Morgan" />
        </Field>
        <Field label="Work email" :error="form.errors.email" v-slot="{ id, invalid, describedby }">
            <Input :id="id" v-model="form.email" type="email" autocomplete="email" required :readonly="Boolean(invitationEmail)" :invalid="invalid" :aria-describedby="describedby" placeholder="you@company.com" />
        </Field>
        <Field v-if="!invitationEmail" label="Workspace name" :error="form.errors.workspace" hint="Usually your company or team name. You can change it later." v-slot="{ id, invalid, describedby }">
            <Input :id="id" v-model="form.workspace" autocomplete="organization" required :invalid="invalid" :aria-describedby="describedby" placeholder="Acme Studio" />
        </Field>
        <Field label="Password" :error="form.errors.password" v-slot="{ id, invalid, describedby }">
            <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" required strength :invalid="invalid" :aria-describedby="describedby" />
        </Field>
        <Field label="Confirm password" :error="form.errors.password_confirmation" v-slot="{ id, invalid }">
            <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" required :invalid="invalid" />
        </Field>
        <div>
            <Checkbox v-model="form.terms">
                I agree to the <Link :href="route('terms')" class="font-medium text-accent-text hover:underline">terms</Link> and
                <Link :href="route('privacy')" class="font-medium text-accent-text hover:underline">privacy policy</Link>.
            </Checkbox>
            <p v-if="form.errors.terms" class="mt-1.5 text-small text-danger" role="alert">{{ form.errors.terms }}</p>
        </div>
        <Button type="submit" size="lg" block :loading="form.processing" :icon-right="ArrowRight">{{ invitationEmail ? 'Create account' : 'Create workspace' }}</Button>
    </form>

    <p class="mt-8 text-center text-body text-ink-3">
        Already have an account?
        <Link :href="route('login')" class="font-medium text-accent-text hover:underline">Log in</Link>
    </p>
</template>
