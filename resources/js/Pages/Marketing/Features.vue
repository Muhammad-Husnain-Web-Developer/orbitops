<script setup>
import { Activity, BarChart3, Briefcase, Building2, Check, Clock, FolderKanban, Globe, Receipt, Users, Wallet } from '@lucide/vue';
import { ref } from 'vue';
import CtaSection from '@/Components/Marketing/CtaSection.vue';
import EngineeringSection from '@/Components/Marketing/EngineeringSection.vue';
import Seo from '@/Components/Marketing/Seo.vue';
import StoryVisual from '@/Components/Marketing/StoryVisual.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);

const modules = [
    { id: 'workspaces', icon: Building2, label: 'Workspaces', title: 'One login. Every business you run.', body: 'Create a workspace for each company, brand or side project. Data, teams, roles, branding and billing stay completely separate.', visual: 'workspace', points: ['Switch workspaces from anywhere', 'Brand colour per workspace', 'Separate roles and permissions', 'Strict tenant isolation'] },
    { id: 'clients', icon: Briefcase, label: 'Clients', title: 'Know every client at a glance.', body: 'Contacts, projects, invoices, files, notes and activity on one page — with what each client owes you always up to date.', visual: 'clients', points: ['Search, filter and sort', 'Internal notes and history', 'Outstanding balances', 'Lead, active and inactive status'] },
    { id: 'projects', icon: FolderKanban, label: 'Projects', title: 'Projects that report their own status.', body: 'Milestones, budgets, deadlines and progress roll up from the work itself, so status meetings become optional.', visual: 'projects', points: ['Milestones with client approvals', 'Budget versus actual', 'Kanban with drag and drop', 'Team and file management'] },
    { id: 'team', icon: Users, label: 'Team', title: 'The right people, with the right access.', body: 'Invite teammates as Owner, Admin, Manager or Member, see utilization and keep everyone focused on the work that matters.', visual: 'team', points: ['Email invitations', 'Editable permission matrix', 'Capacity and utilization', 'Last-active presence'] },
    { id: 'time', icon: Clock, label: 'Time tracking', title: 'Every billable minute, accounted for.', body: 'Start a timer in one click or log time manually. Entries roll into weekly timesheets, project hours and invoices.', visual: 'time', points: ['One running timer per person', 'Manual entries and edits', 'Billable versus non-billable', 'Filter by project and date'] },
    { id: 'invoicing', icon: Receipt, label: 'Invoicing', title: 'Invoices your clients actually pay.', body: 'Build invoices with line items, tax and discounts, send them to the client portal and record payments when they land.', visual: 'invoice', points: ['Draft, sent, paid, overdue, cancelled', 'Automatic overdue detection', 'Print-ready documents', 'Payment notifications'] },
    { id: 'expenses', icon: Wallet, label: 'Expenses', title: 'Know where the money goes.', body: 'Capture receipts, categorise spend, approve or reject it and separate project costs from business overheads.', visual: 'expenses', points: ['Receipt uploads', 'Approval workflow', 'Project and business expenses', 'Monthly trends'] },
    { id: 'portal', icon: Globe, label: 'Client portal', title: 'A portal your clients will love.', body: 'Clients get their own secure space to follow progress, approve milestones, download files, pay invoices and message your team.', visual: 'portal', points: ['Milestone approvals', 'Shared files only when you choose', 'Invoice history', 'Project conversations'] },
    { id: 'reports', icon: BarChart3, label: 'Reports', title: 'Answers, not spreadsheets.', body: 'Revenue, expenses, profit, utilization, hours and collection — filtered by any date range and always current.', visual: 'reports', points: ['Profit and margins', 'Project performance', 'Team utilization', 'Invoice collection'] },
    { id: 'activity', icon: Activity, label: 'Activity logs', title: 'A timeline you can trust.', body: 'Every important action is recorded and streamed live, so the whole team knows what changed and who changed it.', visual: 'activity', points: ['Realtime updates', 'Per-project and per-client history', 'Filter by person and type', 'Audit-friendly records'] },
];

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root">
        <section class="relative isolate overflow-hidden pt-32 pb-16 sm:pt-40">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-grid [mask-image:radial-gradient(ellipse_60%_60%_at_50%_0%,black,transparent)]" aria-hidden="true" />
            <div class="mx-auto max-w-4xl px-5 text-center sm:px-8">
                <p data-reveal class="text-eyebrow text-accent-text uppercase">Features</p>
                <h1 data-reveal class="mt-4 text-[clamp(2.5rem,1.4rem+4.6vw,5rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-balance text-ink">Every tool your business runs on. Finally connected.</h1>
                <p data-reveal class="mx-auto mt-6 max-w-2xl text-lead text-ink-3">Ten modules built on one data model, so information flows from the first client conversation to the final payment without anyone copying it between apps.</p>
            </div>
        </section>

        <nav class="sticky top-16 z-30 border-y border-line bg-canvas/80 backdrop-blur-xl" aria-label="Jump to module">
            <ul class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-5 py-2 sm:px-8 [scrollbar-width:none]">
                <li v-for="module in modules" :key="module.id">
                    <a :href="`#${module.id}`" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-small font-medium whitespace-nowrap text-ink-3 transition-colors hover:bg-hover hover:text-ink">
                        <component :is="module.icon" class="size-3.5" aria-hidden="true" />{{ module.label }}
                    </a>
                </li>
                <li><a href="#security" class="inline-flex rounded-md px-3 py-1.5 text-small font-medium whitespace-nowrap text-ink-3 transition-colors hover:bg-hover hover:text-ink">Security</a></li>
            </ul>
        </nav>

        <div class="mx-auto max-w-7xl space-y-24 px-5 py-24 sm:space-y-32 sm:px-8 sm:py-32">
            <section v-for="(module, index) in modules" :id="module.id" :key="module.id" class="grid scroll-mt-32 items-center gap-10 lg:grid-cols-2 lg:gap-20" :aria-labelledby="`${module.id}-title`">
                <div :class="index % 2 ? 'lg:order-2' : ''">
                    <p data-reveal class="inline-flex items-center gap-2 text-small font-medium text-accent-text"><component :is="module.icon" class="size-4" aria-hidden="true" />{{ module.label }}</p>
                    <h2 :id="`${module.id}-title`" data-reveal class="mt-3 text-[clamp(1.75rem,1.3rem+1.6vw,2.5rem)] leading-[1.08] font-semibold tracking-[-0.035em] text-balance text-ink">{{ module.title }}</h2>
                    <p data-reveal class="mt-4 max-w-lg text-lead text-ink-3">{{ module.body }}</p>
                    <ul data-reveal class="mt-7 grid gap-3 sm:grid-cols-2">
                        <li v-for="point in module.points" :key="point" class="flex items-start gap-2.5 text-body text-ink-2">
                            <Check class="mt-0.5 size-4 shrink-0 text-accent-text" aria-hidden="true" />{{ point }}
                        </li>
                    </ul>
                </div>
                <div data-reveal class="relative" :class="index % 2 ? 'lg:order-1' : ''">
                    <div class="pointer-events-none absolute inset-6 -z-10 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_16%,transparent),transparent)] blur-2xl" aria-hidden="true" />
                    <div class="mx-auto max-w-lg">
                        <StoryVisual :kind="module.visual" />
                    </div>
                </div>
            </section>
        </div>

        <section id="security" class="scroll-mt-32 border-y border-line bg-surface/40">
            <div class="mx-auto max-w-7xl px-5 py-24 sm:px-8 sm:py-32">
                <EngineeringSection />
            </div>
        </section>

        <CtaSection :demo-enabled="demoEnabled" title="See it with your own data." />
    </div>
</template>
