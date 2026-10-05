<script setup>
import { Bell, CheckCircle2, FileText, MessageSquare, Users } from '@lucide/vue';
import { ref } from 'vue';
import { gsap, useGsap } from '@/composables/useGsap';

const root = ref(null);
const track = ref(null);

const stats = [
    { label: 'Progress', value: 78, suffix: '%' },
    { label: 'Tasks', value: 24, suffix: ' / 31' },
    { label: 'Hours', value: 142, suffix: 'h' },
    { label: 'Budget', value: 8400, prefix: '$' },
];

const invoiceItems = [
    ['Visual design — homepage & templates', '$6,960'],
    ['Frontend development sprint', '$5,520'],
    ['QA & accessibility audit', '$1,720'],
];

function countUp(scope, containerAnimation) {
    scope.querySelectorAll('[data-count]').forEach((el) => {
        const target = Number(el.dataset.count);
        const state = { value: 0 };
        el.textContent = '0';

        gsap.to(state, {
            value: target,
            duration: 1.4,
            ease: 'expo.out',
            onUpdate: () => (el.textContent = Math.round(state.value).toLocaleString()),
            scrollTrigger: { trigger: el, start: containerAnimation ? 'left 75%' : 'top 85%', containerAnimation, once: true },
        });
    });
}

useGsap(root, ({ motion, desktop }) => {
    if (!motion) return;

    if (!desktop) {
        countUp(root.value);
        return;
    }

    // Horizontal journey: Project → Client portal → Invoice → Notification.
    const distance = () => track.value.scrollWidth - window.innerWidth + 64;

    const horizontal = gsap.to(track.value, {
        x: () => -distance(),
        ease: 'none',
        scrollTrigger: {
            trigger: '[data-showcase-pin]',
            start: 'top top',
            end: () => `+=${distance()}`,
            pin: true,
            scrub: 0.8,
            invalidateOnRefresh: true,
        },
    });

    countUp(root.value, horizontal);

    gsap.from('[data-connector]', { scaleX: 0, ease: 'none', transformOrigin: 'left center', scrollTrigger: { trigger: '[data-showcase-pin]', start: 'top top', end: () => `+=${distance()}`, scrub: true } });

    gsap.utils.toArray('[data-showcase-panel]').forEach((panel) => {
        gsap.from(panel, { opacity: 0.35, scale: 0.94, ease: 'power2.out', scrollTrigger: { trigger: panel, containerAnimation: horizontal, start: 'left 90%', end: 'left 45%', scrub: true } });
    });

    gsap.from('[data-paid-stamp]', { scale: 2.2, opacity: 0, rotate: -24, ease: 'back.out(2)', duration: 0.6, scrollTrigger: { trigger: '[data-paid-stamp]', containerAnimation: horizontal, start: 'left 70%', toggleActions: 'play none none reverse' } });

    gsap.from('[data-notification]', { y: 24, opacity: 0, stagger: 0.12, duration: 0.6, ease: 'expo.out', scrollTrigger: { trigger: '[data-notification-stack]', containerAnimation: horizontal, start: 'left 75%' } });
});
</script>

<template>
    <section ref="root" class="relative border-y border-line bg-surface/40" aria-labelledby="showcase-title">
        <div data-showcase-pin class="relative overflow-hidden py-20 lg:flex lg:h-screen lg:flex-col lg:justify-center lg:py-0">
            <div class="mx-auto w-full max-w-7xl px-5 sm:px-8">
                <p class="mb-4 inline-flex items-center gap-2 text-eyebrow text-accent-text uppercase"><span class="h-px w-6 bg-accent/60" />From kickoff to cash</p>
                <h2 id="showcase-title" class="max-w-3xl text-h1 text-balance text-ink">One project. Every step connected.</h2>
                <p class="mt-4 max-w-xl text-lead text-ink-3">Work flows from the project board to the client portal, into an invoice and back to your team as a notification — without a single copy-paste.</p>
            </div>

            <div class="relative mt-12 lg:mt-14">
                <div class="pointer-events-none absolute top-[2.15rem] right-0 left-0 hidden h-px bg-line lg:block" aria-hidden="true">
                    <div data-connector class="h-full w-full bg-gradient-to-r from-accent via-accent to-cyan-400" />
                </div>

                <div ref="track" class="flex flex-col gap-6 px-5 sm:px-8 lg:w-max lg:flex-row lg:gap-8 lg:pr-[20vw] lg:pl-[max(2rem,calc((100vw-80rem)/2+2rem))]">
                    <!-- 01 Project -->
                    <article data-showcase-panel class="w-full lg:w-[30rem]">
                        <p class="mb-5 flex items-center gap-3 text-small font-medium text-ink-3"><span class="relative z-10 flex size-7 items-center justify-center rounded-full border border-line bg-canvas text-caption text-ink">01</span>Plan the work</p>
                        <div class="rounded-2xl border border-line bg-surface p-6 shadow-raised">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-caption text-ink-3">Project · Northstar Media</p>
                                    <h3 class="mt-1 text-h2 text-ink">Website Redesign</h3>
                                </div>
                                <span class="rounded-full bg-accent/10 px-2 py-0.5 text-caption font-medium text-accent-text">Active</span>
                            </div>
                            <dl class="mt-6 grid grid-cols-2 gap-3">
                                <div v-for="stat in stats" :key="stat.label" class="rounded-xl border border-line bg-canvas/40 p-4">
                                    <dt class="text-caption text-ink-3">{{ stat.label }}</dt>
                                    <dd class="mt-1.5 text-[1.75rem] leading-none font-semibold tracking-tight text-ink tabular">
                                        {{ stat.prefix }}<span :data-count="stat.value">{{ stat.value.toLocaleString() }}</span>{{ stat.suffix }}
                                    </dd>
                                </div>
                            </dl>
                            <div class="mt-5 h-2 overflow-hidden rounded-full bg-subtle"><div class="h-full w-[78%] rounded-full bg-gradient-to-r from-accent to-cyan-400" /></div>
                            <div class="mt-4 flex items-center justify-between text-caption text-ink-3">
                                <span class="flex items-center gap-1.5"><Users class="size-3.5" />5 people</span>
                                <span>Next: Visual Design review · Fri</span>
                            </div>
                        </div>
                    </article>

                    <!-- 02 Client portal -->
                    <article data-showcase-panel class="w-full lg:w-[30rem]">
                        <p class="mb-5 flex items-center gap-3 text-small font-medium text-ink-3"><span class="relative z-10 flex size-7 items-center justify-center rounded-full border border-line bg-canvas text-caption text-ink">02</span>Share with the client</p>
                        <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-raised">
                            <div class="flex items-center justify-between border-b border-line bg-amber-500/8 px-6 py-3">
                                <span class="text-caption font-medium text-ink-2">Northstar Media · Client portal</span>
                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[0.6875rem] font-medium text-amber-700 dark:text-amber-300">Client view</span>
                            </div>
                            <div class="p-6">
                                <p class="text-caption text-ink-3">Awaiting your approval</p>
                                <p class="mt-1 text-h3 text-ink">Visual Design milestone</p>
                                <p class="mt-2 text-small text-ink-3">Homepage, about and case study templates are ready for review.</p>
                                <div class="mt-4 flex gap-2">
                                    <span class="rounded-lg bg-accent px-3 py-1.5 text-small font-medium text-accent-ink">Approve</span>
                                    <span class="rounded-lg border border-line px-3 py-1.5 text-small font-medium text-ink-2">Request changes</span>
                                </div>
                                <div class="mt-5 flex items-start gap-2.5 rounded-xl bg-subtle/80 p-3">
                                    <MessageSquare class="mt-0.5 size-4 shrink-0 text-ink-3" />
                                    <p class="text-small text-ink-2"><span class="font-medium text-ink">Hannah:</span> The whole team loved the new homepage direction.</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- 03 Invoice -->
                    <article data-showcase-panel class="w-full lg:w-[30rem]">
                        <p class="mb-5 flex items-center gap-3 text-small font-medium text-ink-3"><span class="relative z-10 flex size-7 items-center justify-center rounded-full border border-line bg-canvas text-caption text-ink">03</span>Bill for it</p>
                        <div class="relative rounded-2xl border border-line bg-surface p-6 shadow-raised">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="flex items-center gap-1.5 text-caption text-ink-3"><FileText class="size-3.5" />Invoice</p>
                                    <p class="mt-1 text-h3 text-ink">ACM-1027</p>
                                </div>
                                <div class="text-right text-caption text-ink-3">
                                    <p>Issued Sep 21</p>
                                    <p>Due Oct 5</p>
                                </div>
                            </div>
                            <div class="mt-5 divide-y divide-line border-y border-line">
                                <div v-for="[item, amount] in invoiceItems" :key="item" class="flex justify-between py-2.5 text-small">
                                    <span class="text-ink-2">{{ item }}</span>
                                    <span class="font-medium text-ink tabular">{{ amount }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <span class="text-small text-ink-3">Total due</span>
                                <span class="text-[1.75rem] font-semibold tracking-tight text-ink tabular">$14,200</span>
                            </div>
                            <span data-paid-stamp class="absolute top-7 left-1/2 -translate-x-1/2 rotate-[-12deg] rounded-lg border-2 border-success px-3 py-1 text-small font-bold tracking-[0.18em] text-success uppercase">Paid</span>
                        </div>
                    </article>

                    <!-- 04 Notification -->
                    <article data-showcase-panel class="w-full lg:w-[30rem]">
                        <p class="mb-5 flex items-center gap-3 text-small font-medium text-ink-3"><span class="relative z-10 flex size-7 items-center justify-center rounded-full border border-line bg-canvas text-caption text-ink">04</span>Know instantly</p>
                        <div data-notification-stack class="space-y-3">
                            <div v-for="[icon, title, meta, tone] in [[CheckCircle2, 'Invoice ACM-1027 was paid', 'Apex Commerce paid $14,200', 'text-success bg-success/10'], [CheckCircle2, 'Milestone approved: Visual Design', 'Hannah · Northstar Media', 'text-accent-text bg-accent/10'], [Bell, 'Sarah completed Homepage Design', 'Website Redesign · 18 minutes ago', 'text-info bg-info/10']]" :key="title" data-notification class="flex items-center gap-3.5 rounded-2xl border border-line bg-surface p-4 shadow-raised">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl" :class="tone"><component :is="icon" class="size-5" /></span>
                                <div class="min-w-0">
                                    <p class="text-body font-semibold text-ink">{{ title }}</p>
                                    <p class="text-small text-ink-3">{{ meta }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
