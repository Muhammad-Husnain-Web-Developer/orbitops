<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, Building2, Globe } from '@lucide/vue';
import { ref } from 'vue';
import Alert from '@/Components/UI/Alert.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';

defineProps({
    status: { type: String, default: null },
    canResetPassword: { type: Boolean, default: true },
    demoEnabled: { type: Boolean, default: false },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const demoLoading = ref(null);

function submit() {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}

function demo(persona) {
    router.post(route('demo.login', persona), {}, {
        onStart: () => (demoLoading.value = persona),
        onFinish: () => (demoLoading.value = null),
    });
}
</script>

<template>
    <Head title="Log in" />
    <h1 class="text-h2 text-ink">Welcome back</h1>
    <p class="mt-1.5 text-body text-ink-3">Log in to your OrbitOps workspace.</p>

    <Alert v-if="status" tone="success" class="mt-6">{{ status }}</Alert>

    <div v-if="demoEnabled" class="mt-7 rounded-xl border border-dashed border-line-strong bg-surface/60 p-3">
        <p class="px-1 pb-2.5 text-caption font-medium text-ink-3">Explore the live demo — no account needed</p>
        <div class="grid grid-cols-2 gap-2">
            <Button variant="secondary" size="sm" :icon="Building2" :loading="demoLoading === 'team'" :disabled="Boolean(demoLoading)" @click="demo('team')">Team workspace</Button>
            <Button variant="secondary" size="sm" :icon="Globe" :loading="demoLoading === 'client'" :disabled="Boolean(demoLoading)" @click="demo('client')">Client portal</Button>
        </div>
    </div>

    <div v-if="demoEnabled" class="my-6 flex items-center gap-3 text-caption text-ink-3">
        <span class="h-px flex-1 bg-line" />or continue with email<span class="h-px flex-1 bg-line" />
    </div>

    <form class="space-y-4" :class="demoEnabled ? '' : 'mt-8'" novalidate @submit.prevent="submit">
        <Field label="Email" :error="form.errors.email" v-slot="{ id, invalid, describedby }">
            <Input :id="id" v-model="form.email" type="email" autocomplete="username" autofocus required :invalid="invalid" :aria-describedby="describedby" placeholder="you@company.com" />
        </Field>
        <Field label="Password" :error="form.errors.password">
            <template #aside>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-small font-medium text-accent-text hover:underline">Forgot password?</Link>
            </template>
            <template #default="{ id, invalid, describedby }">
                <PasswordInput :id="id" v-model="form.password" autocomplete="current-password" required :invalid="invalid" :aria-describedby="describedby" />
            </template>
        </Field>
        <Checkbox v-model="form.remember" label="Keep me signed in for 30 days" />
        <Button type="submit" size="lg" block :loading="form.processing" :icon-right="ArrowRight">Log in</Button>
    </form>

    <p class="mt-8 text-center text-body text-ink-3">
        New to OrbitOps?
        <Link :href="route('register')" class="font-medium text-accent-text hover:underline">Create a workspace</Link>
    </p>
</template>
