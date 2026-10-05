<script setup>
import { Check, Minus } from '@lucide/vue';
import { ref } from 'vue';
import CtaSection from '@/Components/Marketing/CtaSection.vue';
import FaqList from '@/Components/Marketing/FaqList.vue';
import PricingTable from '@/Components/Marketing/PricingTable.vue';
import SectionHeading from '@/Components/Marketing/SectionHeading.vue';
import Seo from '@/Components/Marketing/Seo.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
    plans: { type: Object, required: true },
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);

const comparison = [
    { group: 'Workspace', rows: [['Team members', '2', '15', 'Unlimited'], ['Active projects', '5', '50', 'Unlimited'], ['Workspaces', '1', '1', 'Multiple'], ['Storage', '2 GB', '100 GB', '1 TB']] },
    { group: 'Work', rows: [['Clients, tasks & Kanban', true, true, true], ['Time tracking & timesheets', true, true, true], ['Milestones & approvals', false, true, true], ['Calendar', true, true, true]] },
    { group: 'Money', rows: [['Invoices & expenses', true, true, true], ['Overdue automation', false, true, true], ['Reports', 'Basic', 'Full', 'Advanced']] },
    { group: 'Collaboration', rows: [['Client portals', '1 client', 'Every client', 'Every client'], ['Roles & permissions', false, true, true], ['Activity log', '30 days', '1 year', 'Unlimited'], ['API access', false, false, true]] },
];

const faqs = [
    { q: 'Can I try OrbitOps before paying?', a: 'Yes. The Starter plan is free forever, and you can explore a fully populated demo workspace from the homepage without creating an account.' },
    { q: 'How does per-seat pricing work?', a: 'You pay for internal team members only. Client portal users are always free, so you never pay to share work with clients.' },
    { q: 'Can I run more than one business?', a: 'Yes. Scale includes multiple workspaces under one login, each with completely separate data, teams and settings.' },
    { q: 'Is our data isolated from other companies?', a: 'Every record belongs to a workspace and every query is scoped to it automatically, with authorization checks on every action.' },
    { q: 'Can I change plans later?', a: 'Upgrade, downgrade or switch between monthly and yearly billing at any time from Settings → Billing.' },
];

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root">
        <section class="relative isolate overflow-hidden pt-32 pb-20 sm:pt-40">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-grid [mask-image:radial-gradient(ellipse_60%_60%_at_50%_0%,black,transparent)]" aria-hidden="true" />
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <p data-reveal class="text-eyebrow text-accent-text uppercase">Pricing</p>
                    <h1 data-reveal class="mt-4 text-[clamp(2.5rem,1.4rem+4.6vw,5rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-balance text-ink">Pricing that grows with your team.</h1>
                    <p data-reveal class="mx-auto mt-6 max-w-xl text-lead text-ink-3">Every plan includes clients, projects, tasks, time tracking, invoices and expenses. Client portal users are always free.</p>
                </div>
                <div class="mt-14">
                    <PricingTable :plans="plans" />
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-5 pb-24 sm:px-8" aria-labelledby="compare-title">
            <h2 id="compare-title" data-reveal class="text-h2 text-ink">Compare plans</h2>
            <div data-reveal class="mt-6 overflow-x-auto rounded-2xl border border-line bg-surface">
                <table class="w-full min-w-[40rem] text-left">
                    <thead>
                        <tr class="border-b border-line">
                            <th scope="col" class="px-5 py-4 text-small font-medium text-ink-3">Feature</th>
                            <th v-for="plan in plans" :key="plan.name" scope="col" class="px-5 py-4 text-body font-semibold text-ink">{{ plan.name }}</th>
                        </tr>
                    </thead>
                    <tbody v-for="section in comparison" :key="section.group">
                        <tr>
                            <th colspan="4" scope="colgroup" class="bg-subtle/60 px-5 py-2 text-eyebrow text-ink-3 uppercase">{{ section.group }}</th>
                        </tr>
                        <tr v-for="[label, ...values] in section.rows" :key="label" class="border-t border-line">
                            <th scope="row" class="px-5 py-3.5 text-body font-normal text-ink-2">{{ label }}</th>
                            <td v-for="(value, index) in values" :key="index" class="px-5 py-3.5 text-body text-ink">
                                <Check v-if="value === true" class="size-4 text-accent-text" aria-label="Included" />
                                <Minus v-else-if="value === false" class="size-4 text-ink-3" aria-label="Not included" />
                                <span v-else>{{ value }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mx-auto max-w-3xl px-5 pb-24 sm:px-8 sm:pb-32" aria-labelledby="faq-title">
            <SectionHeading heading-id="faq-title" title="Questions, answered." />
            <div class="mt-10">
                <FaqList :items="faqs" />
            </div>
        </section>

        <CtaSection :demo-enabled="demoEnabled" />
    </div>
</template>
