<script setup>
import { Compass, Gauge, HeartHandshake, Lock } from '@lucide/vue';
import { ref } from 'vue';
import CtaSection from '@/Components/Marketing/CtaSection.vue';
import SectionHeading from '@/Components/Marketing/SectionHeading.vue';
import Seo from '@/Components/Marketing/Seo.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);

const principles = [
    { icon: Compass, title: 'Calm by default', body: 'Software for running a business should lower your heart rate. We remove steps, notifications and settings until only the useful ones remain.' },
    { icon: Gauge, title: 'Fast is a feature', body: 'Every screen should respond instantly, work from the keyboard and feel as good on a phone as on a 32-inch display.' },
    { icon: Lock, title: 'Trust is earned', body: 'Your clients trust you with their data, so we treat isolation, permissions and audit history as core product, not add-ons.' },
    { icon: HeartHandshake, title: 'Clients are people', body: 'The client portal is designed with the same care as the internal app, because how you share work is part of the work.' },
];

const numbers = [
    ['10', 'connected modules'],
    ['5', 'roles per workspace'],
    ['1', 'data model for everything'],
    ['0', 'spreadsheets required'],
];

const stack = ['Laravel', 'Inertia.js', 'Vue 3', 'Tailwind CSS', 'MySQL', 'Laravel Reverb', 'GSAP', 'Spatie Permission'];

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root">
        <section class="relative isolate overflow-hidden pt-32 pb-20 sm:pt-44 sm:pb-28">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-grid [mask-image:radial-gradient(ellipse_60%_60%_at_50%_0%,black,transparent)]" aria-hidden="true" />
            <div class="mx-auto max-w-5xl px-5 sm:px-8">
                <p data-reveal class="text-eyebrow text-accent-text uppercase">About OrbitOps</p>
                <h1 data-reveal class="mt-5 text-[clamp(2.5rem,1.4rem+4.6vw,5.25rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-balance text-ink">
                    Great client work deserves better than ten tabs and a spreadsheet.
                </h1>
                <p data-reveal class="mt-8 max-w-2xl text-lead text-ink-3">
                    OrbitOps started from a simple frustration: agencies and studios spend hours every week stitching together project tools, timesheets, invoicing apps and email threads.
                    We are building one calm, connected place to run client work — from first proposal to final invoice.
                </p>
            </div>
        </section>

        <section class="border-y border-line bg-surface/40" aria-label="OrbitOps in numbers">
            <dl class="mx-auto grid max-w-7xl grid-cols-2 divide-line px-5 sm:px-8 lg:grid-cols-4 lg:divide-x">
                <div v-for="[value, label] in numbers" :key="label" data-reveal class="flex flex-col-reverse px-2 py-10 lg:px-8">
                    <dt class="mt-2 text-body text-ink-3">{{ label }}</dt>
                    <dd class="text-[3.25rem] leading-none font-semibold tracking-[-0.05em] text-ink">{{ value }}</dd>
                </div>
            </dl>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-24 sm:px-8 sm:py-32" aria-labelledby="principles-title">
            <SectionHeading heading-id="principles-title" eyebrow="What we believe" title="Principles we build by." align="left" />
            <div class="mt-14 grid gap-4 sm:grid-cols-2">
                <article v-for="principle in principles" :key="principle.title" data-reveal class="rounded-2xl border border-line bg-surface p-7 shadow-card">
                    <component :is="principle.icon" class="size-5 text-accent-text" aria-hidden="true" />
                    <h3 class="mt-5 text-h3 text-ink">{{ principle.title }}</h3>
                    <p class="mt-2 text-body text-ink-3">{{ principle.body }}</p>
                </article>
            </div>
        </section>

        <section class="border-t border-line" aria-labelledby="stack-title">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-24 sm:px-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <p data-reveal class="text-eyebrow text-accent-text uppercase">How it is built</p>
                    <h2 id="stack-title" data-reveal class="mt-4 text-h1 text-ink">Boring technology. Exciting product.</h2>
                    <p data-reveal class="mt-5 max-w-lg text-lead text-ink-3">OrbitOps runs on a proven, server-rendered stack. Business logic lives in Laravel — events, queues, policies and notifications — while Inertia and Vue make every screen feel instant.</p>
                </div>
                <ul data-reveal class="flex flex-wrap gap-2.5">
                    <li v-for="item in stack" :key="item" class="rounded-full border border-line bg-surface px-4 py-2 text-body font-medium text-ink-2 shadow-card">{{ item }}</li>
                </ul>
            </div>
        </section>

        <CtaSection :demo-enabled="demoEnabled" title="Come build with us." />
    </div>
</template>
