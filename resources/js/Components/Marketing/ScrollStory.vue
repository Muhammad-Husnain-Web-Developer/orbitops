<script setup>
import { ref } from 'vue';
import { gsap, useGsap } from '@/composables/useGsap';
import StoryVisual from './StoryVisual.vue';

const steps = [
    { kind: 'workspace', word: 'One workspace.', body: 'Run every company, brand or side business as its own workspace — separate teams, roles, data and branding, one login.' },
    { kind: 'clients', word: 'Your clients.', body: 'Contacts, notes, projects, files and what every client owes you, on one page that the whole team can trust.' },
    { kind: 'projects', word: 'Your projects.', body: 'Milestones, Kanban boards and budgets that update themselves as work moves — no status meetings required.' },
    { kind: 'team', word: 'Your team.', body: 'Invite people with the right role, see who is over capacity and keep everyone on the work that matters.' },
    { kind: 'time', word: 'Your time.', body: 'One-click timers and clean timesheets, tracked against projects and tasks so nothing billable slips away.' },
    { kind: 'revenue', word: 'Your revenue.', body: 'Turn tracked work into professional invoices, follow up on what is overdue and see margins in real time.' },
];

const root = ref(null);
const active = ref(0);

useGsap(root, ({ motion, desktop }) => {
    if (!motion || !desktop) return;

    const visuals = gsap.utils.toArray('[data-story-visual]');
    const words = gsap.utils.toArray('[data-story-word]');
    const bodies = gsap.utils.toArray('[data-story-body]');

    gsap.set(visuals.slice(1), { opacity: 0, y: 60, scale: 0.94, filter: 'blur(6px)' });
    gsap.set(bodies.slice(1), { opacity: 0, y: 12 });
    gsap.set(words.slice(1), { opacity: 0.16 });

    const tl = gsap.timeline({
        defaults: { ease: 'power2.inOut', duration: 1 },
        scrollTrigger: {
            trigger: '[data-story-pin]',
            start: 'top top',
            end: `+=${(steps.length - 1) * 85}%`,
            pin: true,
            scrub: 0.7,
            snap: { snapTo: 'labels', duration: { min: 0.2, max: 0.6 }, ease: 'power1.inOut' },
            onUpdate: (self) => (active.value = Math.round(self.progress * (steps.length - 1))),
        },
    });

    tl.addLabel('step-0');

    for (let i = 1; i < steps.length; i++) {
        tl.to(visuals[i - 1], { opacity: 0, y: -60, scale: 0.94, filter: 'blur(6px)' })
            .to(visuals[i], { opacity: 1, y: 0, scale: 1, filter: 'blur(0px)' }, '<')
            .to(words[i - 1], { opacity: 0.16 }, '<')
            .to(words[i], { opacity: 1 }, '<')
            .to(bodies[i - 1], { opacity: 0, y: -12, duration: 0.5 }, '<')
            .to(bodies[i], { opacity: 1, y: 0, duration: 0.6 }, '<0.4')
            .to('[data-story-progress]', { scaleY: i / (steps.length - 1), ease: 'none' }, '<')
            .addLabel(`step-${i}`);
    }
});
</script>

<template>
    <section ref="root" id="platform" class="relative" aria-labelledby="story-title">
        <h2 id="story-title" class="sr-only">Everything in one workspace</h2>

        <!-- Desktop: pinned, scroll-scrubbed story -->
        <div data-story-pin class="hidden h-screen items-center lg:motion-safe:flex">
            <div class="mx-auto grid w-full max-w-7xl grid-cols-[1fr_1.05fr] items-center gap-16 px-8">
                <div class="relative pl-8">
                    <div class="absolute top-2 bottom-2 left-0 w-px bg-line" aria-hidden="true">
                        <div data-story-progress class="h-full w-full origin-top scale-y-0 bg-accent" />
                    </div>
                    <ol class="space-y-1">
                        <li v-for="(step, index) in steps" :key="step.kind" data-story-word class="text-[clamp(2.25rem,4.4vw,4rem)] leading-[1.05] font-semibold tracking-[-0.045em] text-ink" :aria-current="active === index ? 'step' : undefined">
                            {{ step.word }}
                        </li>
                    </ol>
                    <div class="relative mt-8 h-24 max-w-md">
                        <p v-for="step in steps" :key="step.kind" data-story-body class="absolute inset-0 text-lead text-ink-3">{{ step.body }}</p>
                    </div>
                </div>
                <div class="relative h-[460px]">
                    <div class="pointer-events-none absolute inset-8 -z-10 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_22%,transparent),transparent)] blur-2xl" aria-hidden="true" />
                    <div v-for="step in steps" :key="step.kind" data-story-visual class="absolute inset-0 flex items-center">
                        <StoryVisual :kind="step.kind" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile, tablet and reduced motion: stacked story -->
        <div class="mx-auto max-w-2xl space-y-16 px-5 py-20 sm:px-8 lg:motion-safe:hidden">
            <div v-for="step in steps" :key="step.kind" data-reveal>
                <h3 class="text-h1 text-ink">{{ step.word }}</h3>
                <p class="mt-3 text-lead text-ink-3">{{ step.body }}</p>
                <div class="mt-6"><StoryVisual :kind="step.kind" /></div>
            </div>
        </div>
    </section>
</template>
