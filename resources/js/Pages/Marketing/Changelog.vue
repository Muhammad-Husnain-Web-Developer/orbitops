<script setup>
import { ref } from 'vue';
import CtaSection from '@/Components/Marketing/CtaSection.vue';
import Seo from '@/Components/Marketing/Seo.vue';
import Badge from '@/Components/UI/Badge.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);

const releases = [
    { version: '1.4', date: 'October 2026', title: 'Client portal approvals', tag: 'New', items: ['Clients can approve milestones or request changes from the portal', 'Project message threads between clients and your team', 'Files are only shared with clients when you choose'] },
    { version: '1.3', date: 'September 2026', title: 'Realtime workspace', tag: 'Improved', items: ['Notifications and activity stream live over WebSockets', 'Kanban boards refresh when a teammate moves a card', 'Grouped notification centre with mark-all-as-read'] },
    { version: '1.2', date: 'August 2026', title: 'Reports & command palette', tag: 'New', items: ['Revenue, profit, utilization and collection reports with date filters', 'Press ⌘K anywhere to search and run actions', 'Global search across clients, projects, tasks, invoices and members'] },
    { version: '1.1', date: 'July 2026', title: 'Roles & permissions', tag: 'New', items: ['Per-workspace roles with an editable permission matrix', 'Owner, Admin, Manager, Member and Client roles', 'Team invitations by email'] },
    { version: '1.0', date: 'June 2026', title: 'OrbitOps launches', tag: 'Launch', items: ['Workspaces, clients, projects, tasks and time tracking', 'Invoices, expenses and files', 'Dark and light themes from day one'] },
];

const roadmap = [
    { stage: 'Now', items: ['Recurring invoices', 'Online card payments for invoices', 'Calendar sync'] },
    { stage: 'Next', items: ['Proposals & e-signatures', 'Retainer budgets', 'Custom task fields'] },
    { stage: 'Later', items: ['Native mobile apps', 'Resource planning', 'Public API webhooks'] },
];

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root">
        <section class="mx-auto max-w-4xl px-5 pt-32 pb-16 sm:px-8 sm:pt-40">
            <p data-reveal class="text-eyebrow text-accent-text uppercase">Changelog</p>
            <h1 data-reveal class="mt-4 text-[clamp(2.5rem,1.4rem+4vw,4.5rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-ink">What's new in OrbitOps.</h1>
            <p data-reveal class="mt-6 max-w-xl text-lead text-ink-3">Product updates, improvements and fixes — shipped continuously.</p>
        </section>

        <section class="mx-auto max-w-4xl px-5 pb-24 sm:px-8" aria-label="Releases">
            <ol class="relative border-l border-line">
                <li v-for="release in releases" :key="release.version" data-reveal class="relative pb-14 pl-8 last:pb-0 sm:pl-12">
                    <span class="absolute top-1.5 -left-[5px] size-2.5 rounded-full bg-accent ring-4 ring-canvas" aria-hidden="true" />
                    <p class="text-small text-ink-3"><time>{{ release.date }}</time> · v{{ release.version }}</p>
                    <h2 class="mt-2 flex flex-wrap items-center gap-3 text-h2 text-ink">{{ release.title }} <Badge :tone="release.tag === 'Improved' ? 'info' : 'accent'">{{ release.tag }}</Badge></h2>
                    <ul class="mt-4 space-y-2">
                        <li v-for="item in release.items" :key="item" class="flex gap-3 text-body text-ink-2"><span class="mt-2 size-1 shrink-0 rounded-full bg-ink-3" aria-hidden="true" />{{ item }}</li>
                    </ul>
                </li>
            </ol>
        </section>

        <section id="roadmap" class="scroll-mt-24 border-t border-line bg-surface/40" aria-labelledby="roadmap-title">
            <div class="mx-auto max-w-6xl px-5 py-24 sm:px-8">
                <p data-reveal class="text-eyebrow text-accent-text uppercase">Roadmap</p>
                <h2 id="roadmap-title" data-reveal class="mt-4 text-h1 text-ink">Where we're heading.</h2>
                <div class="mt-12 grid gap-4 md:grid-cols-3">
                    <article v-for="column in roadmap" :key="column.stage" data-reveal class="rounded-2xl border border-line bg-surface p-6 shadow-card">
                        <h3 class="flex items-center gap-2 text-h3 text-ink"><span class="size-2 rounded-full" :class="column.stage === 'Now' ? 'bg-success' : column.stage === 'Next' ? 'bg-accent' : 'bg-ink-3'" aria-hidden="true" />{{ column.stage }}</h3>
                        <ul class="mt-4 space-y-2.5">
                            <li v-for="item in column.items" :key="item" class="rounded-lg border border-line bg-canvas/40 px-3.5 py-2.5 text-body text-ink-2">{{ item }}</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <CtaSection :demo-enabled="demoEnabled" />
    </div>
</template>
