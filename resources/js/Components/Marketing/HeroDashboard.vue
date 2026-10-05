<script setup>
import {
    BarChart3,
    Briefcase,
    Calendar,
    CheckSquare,
    Clock,
    FileText,
    FolderOpen,
    LayoutGrid,
    Receipt,
    Search,
    Settings,
    Users,
    Wallet,
} from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';

/*
 * A static, theme-aware rendering of the OrbitOps overview, used as the hero visual.
 * Designed on a fixed 1120 × 680 canvas and scaled to its container.
 */
const DESIGN_WIDTH = 1120;
const DESIGN_HEIGHT = 680;

const frame = ref(null);
const scale = ref(1);
let observer;

onMounted(() => {
    observer = new ResizeObserver(([entry]) => (scale.value = entry.contentRect.width / DESIGN_WIDTH));
    observer.observe(frame.value);
});

onBeforeUnmount(() => observer?.disconnect());

const nav = [
    [LayoutGrid, 'Overview', true],
    [Briefcase, 'Clients'],
    [FolderOpen, 'Projects'],
    [CheckSquare, 'Tasks'],
    [Calendar, 'Calendar'],
    [Clock, 'Time'],
    [FileText, 'Invoices'],
    [Wallet, 'Expenses'],
    [BarChart3, 'Reports'],
];

const kpis = [
    ['Revenue', '$48,260', '+12.4%', true],
    ['Outstanding', '$14,600', '3 invoices', null],
    ['Active projects', '6', '+2 this month', true],
    ['Team utilization', '84%', '+6.1%', true],
];

const projects = [
    ['Website Redesign', 'Northstar Media', 78, 'bg-chart-1'],
    ['Mobile App', 'Apex Commerce', 52, 'bg-chart-4'],
    ['SaaS Dashboard', 'Vertex Labs', 64, 'bg-chart-2'],
    ['Brand System', 'Lumen Health', 88, 'bg-chart-3'],
];

const activity = [
    ['SC', 'Sarah', 'completed', 'Homepage Design', '18m'],
    ['HB', 'Hannah', 'approved', 'Wireframes', '1h'],
    ['JO', 'James', 'moved', 'CMS content modeling', '3h'],
];

// Revenue curve (pre-computed monotone path on a 560 × 150 plot).
const revenueLine = 'M0,118 C40,112 60,104 93,100 C126,96 150,108 187,96 C224,84 246,64 280,70 C314,76 340,58 373,46 C406,34 430,44 467,30 C504,16 528,22 560,12';
const revenueArea = `${revenueLine} L560,150 L0,150 Z`;
const expenseLine = 'M0,136 C40,134 60,130 93,131 C126,132 150,126 187,127 C224,128 246,120 280,122 C314,124 340,118 373,116 C406,114 430,118 467,112 C504,106 528,110 560,104';
</script>

<template>
    <div ref="frame" class="relative w-full" :style="{ height: `${DESIGN_HEIGHT * scale}px` }">
        <div
            class="absolute top-0 left-0 origin-top-left overflow-hidden rounded-[18px] border border-line bg-surface shadow-[0_40px_120px_-30px_rgb(0_0_0/0.45)]"
            :style="{ width: `${DESIGN_WIDTH}px`, height: `${DESIGN_HEIGHT}px`, transform: `scale(${scale})` }"
            aria-hidden="true"
        >
            <!-- Window chrome -->
            <div class="flex h-11 items-center gap-4 border-b border-line bg-canvas/60 px-4">
                <div class="flex gap-1.5">
                    <span class="size-2.5 rounded-full bg-[#ff5f57]" />
                    <span class="size-2.5 rounded-full bg-[#febc2e]" />
                    <span class="size-2.5 rounded-full bg-[#28c840]" />
                </div>
                <div class="mx-auto flex h-7 w-80 items-center gap-2 rounded-md border border-line bg-surface px-2.5 text-[11px] text-ink-3">
                    <Search class="size-3" />
                    Search clients, projects, invoices…
                    <span class="ml-auto rounded border border-line px-1 text-[10px]">⌘K</span>
                </div>
                <div class="flex -space-x-1.5">
                    <span class="flex size-6 items-center justify-center rounded-full bg-violet-500/20 text-[9px] font-semibold text-violet-600 ring-2 ring-canvas dark:text-violet-300">MR</span>
                    <span class="flex size-6 items-center justify-center rounded-full bg-sky-500/20 text-[9px] font-semibold text-sky-600 ring-2 ring-canvas dark:text-sky-300">SC</span>
                    <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/20 text-[9px] font-semibold text-emerald-600 ring-2 ring-canvas dark:text-emerald-300">JO</span>
                </div>
            </div>

            <div class="flex h-[636px]">
                <!-- Sidebar -->
                <aside class="flex w-[212px] shrink-0 flex-col border-r border-line bg-canvas/40 p-3">
                    <div class="mb-4 flex items-center gap-2 rounded-lg border border-line bg-surface p-2">
                        <span class="flex size-7 items-center justify-center rounded-md bg-accent text-[10px] font-bold text-accent-ink">AS</span>
                        <div>
                            <p class="text-[12px] leading-tight font-semibold text-ink">Acme Studio</p>
                            <p class="text-[10px] text-ink-3">Creative Agency</p>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <div v-for="[icon, label, active] in nav" :key="label" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[12px]" :class="active ? 'bg-accent/10 font-medium text-accent-text' : 'text-ink-3'">
                            <component :is="icon" class="size-3.5" />
                            {{ label }}
                        </div>
                    </div>
                    <div class="mt-auto space-y-0.5 border-t border-line pt-3">
                        <div class="flex items-center gap-2.5 px-2 py-1.5 text-[12px] text-ink-3"><Users class="size-3.5" />Team</div>
                        <div class="flex items-center gap-2.5 px-2 py-1.5 text-[12px] text-ink-3"><Settings class="size-3.5" />Settings</div>
                    </div>
                </aside>

                <!-- Main -->
                <div class="flex-1 space-y-4 overflow-hidden p-5">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[11px] text-ink-3">Monday, October 5</p>
                            <p class="text-[20px] font-semibold tracking-tight text-ink">Good morning, Muhammad</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-[11px] text-ink-2">Last 12 months</span>
                            <span class="rounded-md bg-accent px-2.5 py-1.5 text-[11px] font-medium text-accent-ink">+ New project</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-3">
                        <div v-for="[label, value, delta, up] in kpis" :key="label" class="rounded-xl border border-line bg-surface p-3.5">
                            <p class="text-[11px] text-ink-3">{{ label }}</p>
                            <p class="mt-2 text-[22px] leading-none font-semibold tracking-tight text-ink">{{ value }}</p>
                            <p class="mt-2 text-[10px]" :class="up ? 'text-success' : 'text-ink-3'">{{ delta }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-[1.75fr_1fr] gap-3">
                        <div class="rounded-xl border border-line bg-surface p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-[12px] font-semibold text-ink">Revenue vs expenses</p>
                                <div class="flex items-center gap-3 text-[10px] text-ink-3">
                                    <span class="flex items-center gap-1"><span class="h-0.5 w-2.5 rounded bg-chart-1" />Revenue</span>
                                    <span class="flex items-center gap-1"><span class="h-0.5 w-2.5 rounded bg-chart-2" />Expenses</span>
                                </div>
                            </div>
                            <svg viewBox="0 0 560 150" class="h-[150px] w-full" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="hero-rev" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="var(--chart-1)" stop-opacity="0.22" />
                                        <stop offset="100%" stop-color="var(--chart-1)" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                <line v-for="i in 4" :key="i" x1="0" x2="560" :y1="i * 30" :y2="i * 30" stroke="var(--chart-grid)" />
                                <path :d="revenueArea" fill="url(#hero-rev)" />
                                <path data-hero-line :d="revenueLine" fill="none" stroke="var(--chart-1)" stroke-width="2.2" stroke-linecap="round" />
                                <path :d="expenseLine" fill="none" stroke="var(--chart-2)" stroke-width="2" stroke-linecap="round" />
                                <circle cx="560" cy="12" r="4" fill="var(--chart-1)" stroke="var(--surface)" stroke-width="2" />
                            </svg>
                            <div class="mt-2 flex justify-between text-[10px] text-ink-3">
                                <span v-for="month in ['Nov', 'Jan', 'Mar', 'May', 'Jul', 'Sep']" :key="month">{{ month }}</span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-line bg-surface p-4">
                            <p class="mb-3 text-[12px] font-semibold text-ink">Active projects</p>
                            <div class="space-y-3">
                                <div v-for="[name, client, progress, color] in projects" :key="name">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-medium text-ink">{{ name }}</span>
                                        <span class="text-ink-3">{{ progress }}%</span>
                                    </div>
                                    <p class="text-[10px] text-ink-3">{{ client }}</p>
                                    <div class="mt-1.5 h-1 rounded-full bg-subtle">
                                        <div class="h-full rounded-full" :class="color" :style="{ width: `${progress}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-[1fr_1fr] gap-3">
                        <div class="rounded-xl border border-line bg-surface p-4">
                            <p class="mb-2.5 text-[12px] font-semibold text-ink">Recent activity</p>
                            <div v-for="[initials, who, verb, what, when] in activity" :key="what" class="flex items-center gap-2.5 py-1.5 text-[11px]">
                                <span class="flex size-5 items-center justify-center rounded-full bg-subtle text-[8px] font-semibold text-ink-2">{{ initials }}</span>
                                <span class="text-ink-2"><span class="font-medium text-ink">{{ who }}</span> {{ verb }} <span class="font-medium text-ink">{{ what }}</span></span>
                                <span class="ml-auto text-ink-3">{{ when }}</span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-line bg-surface p-4">
                            <div class="mb-2.5 flex items-center justify-between">
                                <p class="text-[12px] font-semibold text-ink">Upcoming deadlines</p>
                                <Receipt class="size-3.5 text-ink-3" />
                            </div>
                            <div v-for="[title, meta, tone] in [['Visual Design review', 'Website Redesign · Fri', 'text-warning'], ['Cart state & persistence', 'Mobile App · overdue', 'text-danger'], ['Brand guidelines', 'Brand System · Oct 14', 'text-ink-3']]" :key="title" class="flex items-center justify-between py-1.5 text-[11px]">
                                <span class="font-medium text-ink">{{ title }}</span>
                                <span :class="tone">{{ meta }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
