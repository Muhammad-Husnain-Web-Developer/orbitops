<script setup>
import { Activity, BarChart3, Briefcase, Building2, Clock, FolderKanban, Globe, Play, Receipt, Users, Wallet } from '@lucide/vue';

const features = [
    { key: 'workspaces', icon: Building2, title: 'Workspace management', body: 'Run several businesses side by side. Every workspace has its own team, data and brand colour.', span: 'lg:col-span-2' },
    { key: 'clients', icon: Briefcase, title: 'Client management', body: 'Contacts, notes, projects, files and balances for every client, organised and searchable.', span: 'lg:col-span-2' },
    { key: 'projects', icon: FolderKanban, title: 'Project management', body: 'Milestones, Kanban boards, budgets and progress that updates as work moves.', span: 'lg:col-span-2' },
    { key: 'time', icon: Clock, title: 'Time tracking', body: 'One-click timers and manual entries against projects and tasks, with clean weekly timesheets.', span: 'lg:col-span-3' },
    { key: 'invoicing', icon: Receipt, title: 'Invoicing', body: 'Professional invoices with line items, tax and discounts. Send, track and record payments.', span: 'lg:col-span-3' },
    { key: 'team', icon: Users, title: 'Team management', body: 'Invite teammates as Owner, Admin, Manager or Member and tune exactly what each role can do.', span: 'lg:col-span-2' },
    { key: 'expenses', icon: Wallet, title: 'Expenses', body: 'Capture receipts, approve spend and see project and business costs side by side.', span: 'lg:col-span-2' },
    { key: 'portal', icon: Globe, title: 'Client portal', body: 'A secure space where clients follow progress, approve milestones, pay invoices and message you.', span: 'lg:col-span-2' },
    { key: 'reports', icon: BarChart3, title: 'Reports', body: 'Revenue, profit, utilization and collection reports with date filters that answer real questions.', span: 'lg:col-span-3' },
    { key: 'activity', icon: Activity, title: 'Activity logs', body: 'A clear timeline of who did what and when, across every project and client.', span: 'lg:col-span-3' },
];

function spotlight(event) {
    const card = event.currentTarget;
    const rect = card.getBoundingClientRect();
    card.style.setProperty('--x', `${event.clientX - rect.left}px`);
    card.style.setProperty('--y', `${event.clientY - rect.top}px`);
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <article
            v-for="feature in features"
            :key="feature.key"
            data-reveal
            class="group relative flex min-h-[19rem] flex-col overflow-hidden rounded-2xl border border-line bg-surface p-6 shadow-card transition-colors duration-300 hover:border-line-strong"
            :class="feature.span"
            @pointermove="spotlight"
        >
            <div class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: radial-gradient(420px circle at var(--x, 50%) var(--y, 50%), color-mix(in oklab, var(--accent) 9%, transparent), transparent 60%)" aria-hidden="true" />

            <div class="relative flex size-9 items-center justify-center rounded-xl border border-line bg-canvas/60 text-accent-text">
                <component :is="feature.icon" class="size-4" aria-hidden="true" />
            </div>
            <h3 class="relative mt-5 text-h3 text-ink">{{ feature.title }}</h3>
            <p class="relative mt-1.5 max-w-md text-body text-ink-3">{{ feature.body }}</p>

            <div class="relative mt-auto pt-6" aria-hidden="true">
                <!-- Mini illustrations, one per feature -->
                <div v-if="feature.key === 'workspaces'" class="space-y-1.5">
                    <div v-for="[initials, name, color, offset] in [['AS', 'Acme Studio', 'bg-[#6d5dfc] dark:bg-[#8b7cff]', ''], ['NL', 'Nova Labs', 'bg-[#0891b2] dark:bg-[#22d3ee]', 'translate-x-3'], ['PF', 'PixelFoundry', 'bg-[#e11d48] dark:bg-[#fb7185]', 'translate-x-6']]" :key="name" class="flex items-center gap-2 rounded-lg border border-line bg-canvas/50 p-2 transition-transform duration-500 group-hover:translate-x-0" :class="offset">
                        <span class="flex size-6 items-center justify-center rounded-md text-[0.625rem] font-bold text-white" :class="color">{{ initials }}</span>
                        <span class="text-small font-medium text-ink-2">{{ name }}</span>
                    </div>
                </div>

                <div v-else-if="feature.key === 'clients'" class="divide-y divide-line rounded-lg border border-line bg-canvas/50">
                    <div v-for="[name, status] in [['Northstar Media', 'bg-success'], ['Vertex Labs', 'bg-success'], ['Harbor & Co.', 'bg-info']]" :key="name" class="flex items-center justify-between px-3 py-2 text-small">
                        <span class="text-ink-2">{{ name }}</span>
                        <span class="size-1.5 rounded-full" :class="status" />
                    </div>
                </div>

                <div v-else-if="feature.key === 'projects'" class="rounded-lg border border-line bg-canvas/50 p-3">
                    <div class="flex items-center justify-between text-caption text-ink-3"><span>Milestones</span><span>3 / 5</span></div>
                    <div class="relative mt-3 flex items-center justify-between">
                        <div class="absolute inset-x-1 top-1/2 h-px bg-line" />
                        <div class="absolute top-1/2 left-1 h-px w-1/2 bg-accent transition-[width] duration-700 group-hover:w-[74%]" />
                        <span v-for="n in 5" :key="n" class="relative size-3 rounded-full border-2" :class="n <= 3 ? 'border-accent bg-accent' : 'border-line-strong bg-surface'" />
                    </div>
                </div>

                <div v-else-if="feature.key === 'time'" class="flex items-center justify-between rounded-xl border border-line bg-canvas/50 p-4">
                    <div>
                        <p class="text-caption text-ink-3">Website Redesign · Build homepage</p>
                        <p class="mt-1 font-mono text-[2rem] leading-none font-medium tracking-tight text-ink tabular">01:27:43</p>
                    </div>
                    <span class="flex size-11 items-center justify-center rounded-full bg-accent text-accent-ink transition-transform duration-300 group-hover:scale-110"><Play class="size-4 fill-current" /></span>
                </div>

                <div v-else-if="feature.key === 'invoicing'" class="rounded-xl border border-line bg-canvas/50 p-4">
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="[label, tone] in [['Draft', 'bg-subtle text-ink-2'], ['Sent', 'bg-info/10 text-info'], ['Paid', 'bg-success/10 text-success'], ['Overdue', 'bg-danger/10 text-danger']]" :key="label" class="rounded-full px-2 py-0.5 text-caption font-medium" :class="tone">{{ label }}</span>
                    </div>
                    <div class="mt-3 flex items-end justify-between border-t border-line pt-3">
                        <span class="text-small text-ink-3">ACM-1027 · Apex Commerce</span>
                        <span class="text-h3 text-ink tabular">$14,200.00</span>
                    </div>
                </div>

                <div v-else-if="feature.key === 'team'" class="flex flex-wrap gap-1.5">
                    <span v-for="[role, tone] in [['Owner', 'bg-accent/10 text-accent-text'], ['Admin', 'bg-info/10 text-info'], ['Manager', 'bg-success/10 text-success'], ['Member', 'bg-subtle text-ink-2'], ['Client', 'bg-warning/10 text-warning']]" :key="role" class="rounded-full px-2.5 py-1 text-caption font-medium" :class="tone">{{ role }}</span>
                </div>

                <div v-else-if="feature.key === 'expenses'" class="space-y-2">
                    <div v-for="[label, width] in [['Contractors', 'w-[82%]'], ['Software', 'w-[58%]'], ['Travel', 'w-[34%]']]" :key="label" class="text-caption text-ink-3">
                        <div class="mb-1 flex justify-between"><span>{{ label }}</span></div>
                        <div class="h-1.5 rounded-full bg-subtle"><div class="h-full rounded-full bg-chart-3 transition-[width] duration-700" :class="width" /></div>
                    </div>
                </div>

                <div v-else-if="feature.key === 'portal'" class="overflow-hidden rounded-lg border border-line bg-canvas/50">
                    <div class="flex items-center gap-1 border-b border-line px-2.5 py-1.5"><span v-for="n in 3" :key="n" class="size-1.5 rounded-full bg-line-strong" /><span class="ml-2 text-[0.625rem] text-ink-3">portal · Northstar Media</span></div>
                    <div class="flex items-center justify-between p-3">
                        <span class="text-small text-ink-2">Visual Design</span>
                        <span class="rounded-md bg-accent px-2 py-0.5 text-caption font-medium text-accent-ink">Approve</span>
                    </div>
                </div>

                <div v-else-if="feature.key === 'reports'" class="flex items-end gap-4">
                    <div class="flex h-24 flex-1 items-end gap-1.5">
                        <div v-for="(height, index) in [38, 52, 45, 64, 58, 72, 66, 84]" :key="index" class="flex-1 rounded-t-[4px] bg-chart-1 transition-all duration-500" :class="index === 7 ? '' : 'opacity-40 group-hover:opacity-60'" :style="{ height: `${height}%` }" />
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-caption text-ink-3">Profit margin</p>
                        <p class="text-h2 text-success">62%</p>
                    </div>
                </div>

                <div v-else class="space-y-2.5">
                    <div v-for="[who, what, when] in [['Sarah', 'completed Homepage Design', '18m'], ['Hannah', 'approved Wireframes', '1h'], ['Emma', 'sent invoice ACM-1024', '4h']]" :key="what" class="flex items-center gap-2.5 text-small">
                        <span class="size-1.5 shrink-0 rounded-full bg-accent" />
                        <span class="text-ink-2"><span class="font-medium text-ink">{{ who }}</span> {{ what }}</span>
                        <span class="ml-auto text-caption text-ink-3">{{ when }}</span>
                    </div>
                </div>
            </div>
        </article>
    </div>
</template>
