<script setup>
import { Building2, Check, CheckCircle2, Clock, FileText, MessageSquare, Paperclip, Play, Receipt, TrendingUp } from '@lucide/vue';

defineProps({
    kind: { type: String, required: true },
});

const workspaces = [
    ['AS', 'Acme Studio', 'Creative Agency', 'bg-[#5b4af0]', true],
    ['NL', 'Nova Labs', 'Software Company', 'bg-[#0e7490]', false],
    ['PF', 'PixelFoundry', 'Design Studio', 'bg-[#be123c]', false],
];

const clients = [
    ['NM', 'Northstar Media', 'Media & Publishing', 'Active', '$12,400'],
    ['VL', 'Vertex Labs', 'Biotech', 'Active', '$8,750'],
    ['AC', 'Apex Commerce', 'E-commerce', 'Active', '$21,300'],
    ['HC', 'Harbor & Co.', 'Hospitality', 'Lead', '—'],
];

const columns = [
    ['To Do', [['Integrate newsletter signup', 'Medium'], ['SEO metadata', 'Medium']]],
    ['In Progress', [['Build homepage sections', 'High'], ['CMS content modeling', 'High']]],
    ['Done', [['Homepage Design', 'Urgent']]],
];

const team = [
    ['SC', 'Sarah Chen', 'Product Design', 92],
    ['JO', 'James Okafor', 'Engineering', 86],
    ['PP', 'Priya Patel', 'Frontend', 78],
    ['LS', 'Lucas Silva', 'Brand', 64],
];

const week = [5.5, 7, 6.25, 7.5, 4.75, 0, 0];
const categories = [
    ['Contractors', 2400, 'w-[86%]'],
    ['Software', 1640, 'w-[59%]'],
    ['Travel', 898, 'w-[32%]'],
    ['Hardware', 1890, 'w-[68%]'],
];
const timeline = [
    ['Sarah', 'completed', 'Homepage Design', '18 minutes ago', 'bg-success'],
    ['Hannah', 'approved milestone', 'Wireframes', '1 hour ago', 'bg-accent'],
    ['Emma', 'sent invoice', 'ACM-1024', '4 hours ago', 'bg-info'],
    ['Priya', 'uploaded', 'Homepage-Hero-v3.png', 'Yesterday', 'bg-warning'],
];
const revenue = [18, 24, 22, 31, 29, 38, 35, 44, 41, 52, 49, 58];
</script>

<template>
    <div class="w-full rounded-2xl border border-line bg-surface p-5 shadow-raised sm:p-6" aria-hidden="true">
        <!-- One workspace -->
        <template v-if="kind === 'workspace'">
            <p class="text-caption font-medium text-ink-3">Switch workspace</p>
            <div class="mt-3 space-y-2">
                <div v-for="[initials, name, industry, color, active] in workspaces" :key="name" class="flex items-center gap-3 rounded-xl border p-3" :class="active ? 'border-accent/40 bg-accent/5' : 'border-line'">
                    <span class="flex size-9 items-center justify-center rounded-lg text-caption font-bold text-white" :class="color">{{ initials }}</span>
                    <div class="flex-1">
                        <p class="text-body font-semibold text-ink">{{ name }}</p>
                        <p class="text-caption text-ink-3">{{ industry }}</p>
                    </div>
                    <Check v-if="active" class="size-4 text-accent-text" />
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-dashed border-line-strong p-3 text-small text-ink-3"><Building2 class="size-4" />Create workspace</div>
            </div>
        </template>

        <!-- Clients -->
        <template v-else-if="kind === 'clients'">
            <div class="flex items-center justify-between">
                <p class="text-body font-semibold text-ink">Clients</p>
                <span class="rounded-md bg-accent px-2.5 py-1 text-caption font-medium text-accent-ink">+ New client</span>
            </div>
            <div class="mt-4 divide-y divide-line">
                <div v-for="[initials, name, industry, status, owed] in clients" :key="name" class="flex items-center gap-3 py-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-subtle text-caption font-semibold text-ink-2">{{ initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-small font-medium text-ink">{{ name }}</p>
                        <p class="text-caption text-ink-3">{{ industry }}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-caption font-medium" :class="status === 'Lead' ? 'bg-info/10 text-info' : 'bg-success/10 text-success'">{{ status }}</span>
                    <span class="w-16 text-right text-small text-ink-2 tabular">{{ owed }}</span>
                </div>
            </div>
        </template>

        <!-- Projects -->
        <template v-else-if="kind === 'projects'">
            <div class="flex items-center justify-between">
                <p class="text-body font-semibold text-ink">Website Redesign</p>
                <span class="text-caption text-ink-3">78% complete</span>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2.5">
                <div v-for="[title, cards] in columns" :key="title" class="rounded-xl bg-subtle/70 p-2">
                    <p class="px-1 pb-2 text-caption font-medium text-ink-3">{{ title }}</p>
                    <div class="space-y-2">
                        <div v-for="[card, priority] in cards" :key="card" class="rounded-lg border border-line bg-surface p-2.5 shadow-card">
                            <p class="text-caption leading-snug font-medium text-ink">{{ card }}</p>
                            <span class="mt-2 inline-block rounded-full px-1.5 text-[0.625rem] font-medium" :class="priority === 'Urgent' ? 'bg-danger/10 text-danger' : priority === 'High' ? 'bg-warning/10 text-warning' : 'bg-info/10 text-info'">{{ priority }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Team -->
        <template v-else-if="kind === 'team'">
            <div class="flex items-center justify-between">
                <p class="text-body font-semibold text-ink">Team utilization</p>
                <span class="text-caption text-ink-3">This week</span>
            </div>
            <div class="mt-4 space-y-3.5">
                <div v-for="[initials, name, role, load] in team" :key="name" class="flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-accent/12 text-caption font-semibold text-accent-text">{{ initials }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between text-small"><span class="font-medium text-ink">{{ name }}</span><span class="text-ink-3 tabular">{{ load }}%</span></div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-subtle"><div class="h-full rounded-full" :class="load > 90 ? 'bg-warning' : 'bg-accent'" :style="{ width: `${load}%` }" /></div>
                        <p class="mt-1 text-caption text-ink-3">{{ role }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Time -->
        <template v-else-if="kind === 'time'">
            <div class="rounded-xl border border-line bg-canvas/50 p-4 text-center">
                <p class="flex items-center justify-center gap-1.5 text-caption text-ink-3"><Clock class="size-3.5" />Website Redesign</p>
                <p class="mt-2 font-mono text-[2.5rem] leading-none font-medium tracking-tight text-ink tabular">00:42:17</p>
                <span class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-accent px-3.5 py-1.5 text-small font-medium text-accent-ink"><Play class="size-3.5" />Start Timer</span>
            </div>
            <div class="mt-4 flex h-24 items-end gap-2">
                <div v-for="(hours, index) in week" :key="index" class="flex flex-1 flex-col items-center gap-1.5">
                    <div class="w-full max-w-6 rounded-t-[4px] bg-chart-1" :class="index === 3 ? '' : 'opacity-55'" :style="{ height: `${(hours / 8) * 72 + 2}px` }" />
                    <span class="text-[0.625rem] text-ink-3">{{ ['M', 'T', 'W', 'T', 'F', 'S', 'S'][index] }}</span>
                </div>
            </div>
        </template>

        <!-- Invoice -->
        <template v-else-if="kind === 'invoice'">
            <div class="flex items-start justify-between">
                <div>
                    <p class="flex items-center gap-1.5 text-caption text-ink-3"><FileText class="size-3.5" />Invoice ACM-1027</p>
                    <p class="mt-1 text-h3 text-ink">Apex Commerce</p>
                </div>
                <span class="rounded-full bg-info/10 px-2 py-0.5 text-caption font-medium text-info">Sent</span>
            </div>
            <div class="mt-4 divide-y divide-line border-y border-line text-small">
                <div class="flex justify-between py-2"><span class="text-ink-2">iOS &amp; Android development sprint · 64h</span><span class="text-ink tabular">$8,000</span></div>
                <div class="flex justify-between py-2"><span class="text-ink-2">QA &amp; device testing · 18h</span><span class="text-ink tabular">$1,710</span></div>
            </div>
            <div class="mt-3 space-y-1 text-small">
                <div class="flex justify-between text-ink-3"><span>Tax (8%)</span><span class="tabular">$776.80</span></div>
                <div class="flex justify-between text-body font-semibold text-ink"><span>Total</span><span class="tabular">$10,486.80</span></div>
            </div>
            <div class="mt-4 flex gap-2">
                <span class="flex-1 rounded-lg bg-accent px-3 py-1.5 text-center text-small font-medium text-accent-ink">Record payment</span>
                <span class="rounded-lg border border-line px-3 py-1.5 text-small font-medium text-ink-2">Download</span>
            </div>
        </template>

        <!-- Expenses -->
        <template v-else-if="kind === 'expenses'">
            <div class="flex items-center justify-between">
                <p class="text-body font-semibold text-ink">Expenses · this quarter</p>
                <Receipt class="size-4 text-ink-3" />
            </div>
            <p class="mt-2 text-[1.75rem] font-semibold tracking-tight text-ink">$6,828</p>
            <div class="mt-4 space-y-3">
                <div v-for="[label, amount, width] in categories" :key="label">
                    <div class="flex justify-between text-small"><span class="text-ink-2">{{ label }}</span><span class="text-ink tabular">${{ amount.toLocaleString() }}</span></div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-subtle"><div class="h-full rounded-full bg-chart-3" :class="width" /></div>
                </div>
            </div>
        </template>

        <!-- Client portal -->
        <template v-else-if="kind === 'portal'">
            <div class="-mx-5 -mt-5 mb-4 flex items-center justify-between border-b border-line bg-amber-500/8 px-5 py-2.5 sm:-mx-6 sm:-mt-6 sm:px-6">
                <span class="text-caption font-medium text-ink-2">Northstar Media · Client portal</span>
                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[0.6875rem] font-medium text-amber-800 dark:text-amber-300">Client view</span>
            </div>
            <p class="text-caption text-ink-3">Website Redesign</p>
            <div class="mt-2 h-2 rounded-full bg-subtle"><div class="h-full w-[78%] rounded-full bg-accent" /></div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between rounded-lg border border-line p-2.5 text-small"><span class="flex items-center gap-2 text-ink"><CheckCircle2 class="size-4 text-success" />Wireframes</span><span class="text-ink-3">Approved</span></div>
                <div class="flex items-center justify-between rounded-lg border border-accent/30 bg-accent/5 p-2.5 text-small"><span class="text-ink">Visual Design</span><span class="rounded-md bg-accent px-2 py-0.5 text-caption font-medium text-accent-ink">Review</span></div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-caption text-ink-3"><MessageSquare class="size-3.5" />3 new messages<span class="mx-1">·</span><Paperclip class="size-3.5" />6 shared files</div>
        </template>

        <!-- Reports -->
        <template v-else-if="kind === 'reports'">
            <div class="grid grid-cols-3 gap-2">
                <div v-for="[label, value, tone] in [['Revenue', '$281.9k', 'text-ink'], ['Expenses', '$45.2k', 'text-ink'], ['Profit', '$236.7k', 'text-success']]" :key="label" class="rounded-lg border border-line p-2.5">
                    <p class="text-caption text-ink-3">{{ label }}</p>
                    <p class="mt-1 text-body font-semibold" :class="tone">{{ value }}</p>
                </div>
            </div>
            <div class="mt-5 flex h-28 items-end gap-1.5">
                <div v-for="(value, index) in revenue" :key="index" class="flex flex-1 flex-col justify-end gap-0.5">
                    <div class="rounded-t-[3px] bg-chart-1" :style="{ height: `${value * 1.6}px` }" />
                    <div class="bg-chart-2" :style="{ height: `${value * 0.35}px` }" />
                </div>
            </div>
            <div class="mt-3 flex gap-4 text-caption text-ink-3">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-[2px] bg-chart-1" />Revenue</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-[2px] bg-chart-2" />Expenses</span>
            </div>
        </template>

        <!-- Activity -->
        <template v-else-if="kind === 'activity'">
            <p class="text-caption font-medium text-ink-3 uppercase">Today</p>
            <ol class="relative mt-3 space-y-4 border-l border-line pl-5">
                <li v-for="[who, verb, what, when, dot] in timeline" :key="what" class="relative">
                    <span class="absolute top-1.5 -left-[25px] size-2.5 rounded-full ring-4 ring-surface" :class="dot" />
                    <p class="text-small text-ink-2"><span class="font-semibold text-ink">{{ who }}</span> {{ verb }} <span class="font-medium text-ink">{{ what }}</span></p>
                    <p class="text-caption text-ink-3">{{ when }}</p>
                </li>
            </ol>
        </template>

        <!-- Revenue -->
        <template v-else>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-caption text-ink-3">Revenue · last 12 months</p>
                    <p class="mt-1 text-[1.75rem] font-semibold tracking-tight text-ink">$281,925</p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-caption font-medium text-success"><TrendingUp class="size-3" />+24%</span>
            </div>
            <div class="mt-5 flex h-32 items-end gap-1.5">
                <div v-for="(value, index) in revenue" :key="index" class="flex-1 rounded-t-[4px] bg-chart-1" :class="index === revenue.length - 1 ? '' : 'opacity-40'" :style="{ height: `${(value / 60) * 100}%` }" />
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 border-t border-line pt-4 text-center">
                <div><p class="text-caption text-ink-3">Paid</p><p class="text-small font-semibold text-ink">$281.9k</p></div>
                <div><p class="text-caption text-ink-3">Outstanding</p><p class="text-small font-semibold text-ink">$89.8k</p></div>
                <div><p class="text-caption text-ink-3">Margin</p><p class="text-small font-semibold text-success">62%</p></div>
            </div>
        </template>
    </div>
</template>
