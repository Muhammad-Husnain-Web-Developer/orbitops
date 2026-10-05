<script setup>
import { useForm } from '@inertiajs/vue3';
import { CircleCheck, LifeBuoy, Mail, Newspaper, Send } from '@lucide/vue';
import { ref } from 'vue';
import Seo from '@/Components/Marketing/Seo.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
});

const root = ref(null);
const sent = ref(false);

const form = useForm({
    name: '',
    email: '',
    company: '',
    team_size: '',
    topic: 'demo',
    message: '',
    website: '',
});

const topics = [
    { value: 'demo', label: 'Book a demo' },
    { value: 'sales', label: 'Pricing & plans' },
    { value: 'migration', label: 'Migrating from another tool' },
    { value: 'support', label: 'Product support' },
    { value: 'partnership', label: 'Partnerships' },
];

const sizes = ['Just me', '2–10', '11–50', '51–200', '200+'].map((value) => ({ value, label: value }));

const channels = [
    { icon: Mail, title: 'Sales', body: 'Plans, demos and custom onboarding.', link: 'sales@orbitops.app' },
    { icon: LifeBuoy, title: 'Support', body: 'Help with your workspace, any day.', link: 'support@orbitops.app' },
    { icon: Newspaper, title: 'Press', body: 'Brand assets and interviews.', link: 'press@orbitops.app' },
];

function submit() {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => {
            sent.value = true;
            form.reset();
        },
    });
}

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root" class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-grid [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,black,transparent)]" aria-hidden="true" />
        <div class="mx-auto grid max-w-7xl gap-14 px-5 pt-32 pb-24 sm:px-8 sm:pt-40 lg:grid-cols-[1fr_1.15fr] lg:gap-20">
            <div>
                <p data-reveal class="text-eyebrow text-accent-text uppercase">Contact</p>
                <h1 data-reveal class="mt-4 text-[clamp(2.5rem,1.4rem+4vw,4.5rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-balance text-ink">Let's talk about your workflow.</h1>
                <p data-reveal class="mt-6 max-w-md text-lead text-ink-3">Tell us how your team works today. We usually reply within one business day.</p>
                <ul class="mt-12 space-y-4">
                    <li v-for="channel in channels" :key="channel.title" data-reveal class="flex gap-4 rounded-2xl border border-line bg-surface p-5 shadow-card">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent-text"><component :is="channel.icon" class="size-4" aria-hidden="true" /></span>
                        <div>
                            <h2 class="text-body font-semibold text-ink">{{ channel.title }}</h2>
                            <p class="text-small text-ink-3">{{ channel.body }}</p>
                            <a :href="`mailto:${channel.link}`" class="mt-1 inline-block text-small font-medium text-accent-text hover:underline">{{ channel.link }}</a>
                        </div>
                    </li>
                </ul>
            </div>

            <div data-reveal class="relative">
                <Transition mode="out-in" enter-active-class="duration-300 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0 translate-y-2" leave-active-class="duration-150" leave-to-class="opacity-0">
                    <div v-if="sent" key="sent" class="flex flex-col items-center rounded-2xl border border-line bg-surface px-8 py-16 text-center shadow-raised" role="status">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-success/12"><CircleCheck class="size-7 text-success" /></span>
                        <h2 class="mt-6 text-h2 text-ink">Message received.</h2>
                        <p class="mt-2 max-w-sm text-body text-ink-3">Thanks for reaching out. Someone from the team will get back to you within one business day.</p>
                        <Button class="mt-8" variant="secondary" @click="sent = false">Send another message</Button>
                    </div>
                    <form v-else key="form" class="space-y-5 rounded-2xl border border-line bg-surface p-6 shadow-raised sm:p-8" novalidate @submit.prevent="submit">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Field label="Full name" :error="form.errors.name" required v-slot="{ id, invalid, describedby }">
                                <Input :id="id" v-model="form.name" :invalid="invalid" :aria-describedby="describedby" autocomplete="name" placeholder="Alex Morgan" />
                            </Field>
                            <Field label="Work email" :error="form.errors.email" required v-slot="{ id, invalid, describedby }">
                                <Input :id="id" v-model="form.email" type="email" :invalid="invalid" :aria-describedby="describedby" autocomplete="email" placeholder="alex@studio.com" />
                            </Field>
                            <Field label="Company" :error="form.errors.company" optional v-slot="{ id, invalid }">
                                <Input :id="id" v-model="form.company" :invalid="invalid" autocomplete="organization" placeholder="Studio name" />
                            </Field>
                            <Field label="Team size" :error="form.errors.team_size" optional v-slot="{ id, invalid }">
                                <Select :id="id" v-model="form.team_size" :options="sizes" placeholder="Select…" :invalid="invalid" />
                            </Field>
                        </div>
                        <Field label="What can we help with?" :error="form.errors.topic" v-slot="{ id, invalid }">
                            <Select :id="id" v-model="form.topic" :options="topics" :invalid="invalid" />
                        </Field>
                        <Field label="Message" :error="form.errors.message" required hint="A few sentences about your team and what you need." v-slot="{ id, invalid, describedby }">
                            <Textarea :id="id" v-model="form.message" :rows="5" :invalid="invalid" :aria-describedby="describedby" placeholder="We're a 12-person studio juggling…" />
                        </Field>
                        <div class="hidden" aria-hidden="true">
                            <label>Website <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" /></label>
                        </div>
                        <div class="flex flex-col-reverse items-start gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-small text-ink-3">By sending, you agree to our <a :href="route('privacy')" class="text-ink-2 underline underline-offset-4 hover:text-ink">privacy policy</a>.</p>
                            <Button type="submit" size="lg" :icon-right="Send" :loading="form.processing" class="w-full sm:w-auto">Send message</Button>
                        </div>
                    </form>
                </Transition>
            </div>
        </div>
    </div>
</template>
