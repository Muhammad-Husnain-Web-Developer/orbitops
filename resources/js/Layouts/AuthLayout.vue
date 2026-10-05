<script setup>
import { Link } from '@inertiajs/vue3';
import { CircleCheck, Clock, Receipt, ShieldCheck } from '@lucide/vue';
import Logo from '@/Components/UI/Logo.vue';
import ThemeToggle from '@/Components/UI/ThemeToggle.vue';
import Toaster from '@/Components/UI/Toaster.vue';

const highlights = [
    { icon: Clock, text: 'Track time and turn it into invoices in two clicks.' },
    { icon: ShieldCheck, text: 'Every workspace is isolated, with roles you control.' },
    { icon: Receipt, text: 'Clients approve work and pay invoices in their own portal.' },
];
</script>

<template>
    <div class="grid min-h-dvh bg-canvas lg:grid-cols-[1fr_1.05fr]">
        <div class="flex min-h-dvh flex-col px-6 py-6 sm:px-10">
            <header class="flex items-center justify-between">
                <Link :href="route('home')" aria-label="OrbitOps home"><Logo /></Link>
                <ThemeToggle />
            </header>
            <main class="flex flex-1 items-center justify-center py-12">
                <div class="w-full max-w-[25rem] animate-page-in">
                    <slot />
                </div>
            </main>
            <footer class="flex flex-wrap items-center justify-between gap-3 text-small text-ink-3">
                <p>© {{ new Date().getFullYear() }} OrbitOps</p>
                <nav class="flex gap-4" aria-label="Legal">
                    <Link :href="route('privacy')" class="hover:text-ink">Privacy</Link>
                    <Link :href="route('terms')" class="hover:text-ink">Terms</Link>
                    <Link :href="route('contact')" class="hover:text-ink">Help</Link>
                </nav>
            </footer>
        </div>

        <aside class="relative isolate hidden overflow-hidden border-l border-line bg-surface lg:flex lg:flex-col lg:justify-between lg:p-12" aria-hidden="true">
            <div class="absolute inset-0 -z-10 bg-grid [mask-image:radial-gradient(ellipse_80%_70%_at_70%_30%,black,transparent)]" />
            <div class="absolute -top-40 -right-40 -z-10 size-[36rem] rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_28%,transparent),transparent)] blur-2xl" />
            <svg viewBox="0 0 600 600" class="auth-orbit absolute -right-48 -bottom-48 -z-10 size-[42rem] text-line-strong" fill="none">
                <circle cx="300" cy="300" r="290" stroke="currentColor" stroke-dasharray="2 8" />
                <circle cx="300" cy="300" r="200" stroke="currentColor" />
                <circle cx="500" cy="300" r="6" class="fill-accent" />
                <circle cx="300" cy="10" r="4" fill="#22d3ee" />
            </svg>

            <p class="text-eyebrow text-accent-text uppercase">OrbitOps</p>

            <div class="max-w-md">
                <p class="text-[2.5rem] leading-[1.02] font-semibold tracking-[-0.045em] text-ink">Run your business.<br /><span class="text-ink-3">Not your spreadsheets.</span></p>
                <ul class="mt-8 space-y-4">
                    <li v-for="item in highlights" :key="item.text" class="flex items-start gap-3 text-body text-ink-2">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-line bg-elevated text-accent-text shadow-card"><component :is="item.icon" class="size-4" /></span>
                        <span class="pt-1.5">{{ item.text }}</span>
                    </li>
                </ul>
            </div>

            <div class="relative h-40">
                <div class="absolute bottom-0 left-0 w-72 rotate-[-3deg] rounded-xl border border-line bg-elevated p-4 shadow-overlay">
                    <p class="text-caption text-ink-3">Revenue this month</p>
                    <p class="mt-1 text-h2 text-ink tabular">$48,260</p>
                    <div class="mt-3 flex h-10 items-end gap-1">
                        <span v-for="(h, i) in [30, 45, 38, 60, 52, 70, 64, 88]" :key="i" class="flex-1 rounded-t-[3px] bg-chart-1" :class="i === 7 ? '' : 'opacity-40'" :style="{ height: `${h}%` }" />
                    </div>
                </div>
                <div class="absolute bottom-16 left-56 flex w-64 rotate-[2deg] items-center gap-3 rounded-xl border border-line bg-elevated p-3.5 shadow-overlay">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-success/12"><CircleCheck class="size-4 text-success" /></span>
                    <div>
                        <p class="text-small font-semibold text-ink">Invoice ACM-1027 paid</p>
                        <p class="text-caption text-ink-3">Apex Commerce · $14,200</p>
                    </div>
                </div>
            </div>
        </aside>
        <Toaster />
    </div>
</template>

<style scoped>
.auth-orbit {
    animation: spin 120s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .auth-orbit {
        animation: none;
    }
}
</style>
