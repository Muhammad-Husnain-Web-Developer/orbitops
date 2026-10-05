<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, CircleCheck, Play, Sparkles } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import { gsap, useGsap } from '@/composables/useGsap';
import { formatDuration } from '@/lib/format';
import HeroDashboard from './HeroDashboard.vue';

defineProps({
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);
const stage = ref(null);
const headline = [['Run', 'your', 'business.'], ['Not', 'your', 'spreadsheets.']];

// A live timer on a floating card makes the product feel alive.
const elapsed = ref(42 * 60 + 17);
let ticker;
onMounted(() => (ticker = setInterval(() => elapsed.value++, 1000)));
onBeforeUnmount(() => clearInterval(ticker));
const clock = computed(() => formatDuration(elapsed.value, { clock: true }));

const demoProcessing = ref(false);
function startDemo() {
    router.post(route('demo.login'), {}, { onStart: () => (demoProcessing.value = true), onFinish: () => (demoProcessing.value = false) });
}

useGsap(root, ({ motion, desktop }) => {
    if (!motion) return;

    const header = document.querySelector('[data-site-header] nav');
    const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });

    // 1. Background, 2. navigation, 3. headline, 4. copy, 5. CTAs, 6. dashboard, 7. floating cards.
    tl.from('[data-hero-bg]', { opacity: 0, duration: 1.1, ease: 'power2.out' })
        .from(header, { opacity: 0, y: -10, duration: 0.6, clearProps: 'all' }, 0.1)
        .from('[data-hero-badge]', { opacity: 0, y: 10, duration: 0.6 }, 0.2)
        .from('[data-hero-word]', { yPercent: 115, duration: 0.95, stagger: 0.045 }, 0.28)
        .from('[data-hero-sub]', { opacity: 0, y: 16, duration: 0.8 }, 0.72)
        .from('[data-hero-cta] > *', { opacity: 0, y: 12, duration: 0.7, stagger: 0.07 }, 0.84)
        .from('[data-hero-mock]', { opacity: 0, y: 90, rotateX: 32, duration: 1.4 }, 0.9)
        .from('[data-hero-float]', { opacity: 0, y: 26, scale: 0.94, duration: 0.9, stagger: 0.1 }, 1.35)
        .fromTo('[data-hero-line]', { strokeDasharray: 900, strokeDashoffset: 900 }, { strokeDashoffset: 0, duration: 1.6, ease: 'power2.inOut' }, 1.1);

    // 8. Subtle continuous movement.
    gsap.utils.toArray('[data-hero-float]').forEach((card, index) => {
        gsap.to(card, { y: index % 2 ? 9 : -9, duration: 3.2 + index * 0.45, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: 2.2 });
    });

    // Scroll: the dashboard settles back while cards drift at different depths.
    gsap.to('[data-hero-mock]', { rotateX: 0, scale: 0.98, ease: 'none', scrollTrigger: { trigger: '[data-hero-stage]', start: 'top 70%', end: 'bottom top', scrub: 0.6 } });
    gsap.utils.toArray('[data-hero-float]').forEach((card) => {
        gsap.to(card, { yPercent: -Number(card.dataset.depth ?? 1) * 40, ease: 'none', scrollTrigger: { trigger: '[data-hero-stage]', start: 'top 60%', end: 'bottom top', scrub: true } });
    });

    if (!desktop) return;

    // Mouse parallax on pointer devices.
    const tiltX = gsap.quickTo('[data-hero-tilt]', 'rotationY', { duration: 0.9, ease: 'power3.out' });
    const tiltY = gsap.quickTo('[data-hero-tilt]', 'rotationX', { duration: 0.9, ease: 'power3.out' });
    const floats = gsap.utils.toArray('[data-hero-float]').map((card) => ({
        depth: Number(card.dataset.depth ?? 1),
        x: gsap.quickTo(card, 'x', { duration: 1, ease: 'power3.out' }),
    }));

    const onMove = (event) => {
        const rect = stage.value.getBoundingClientRect();
        const px = (event.clientX - rect.left) / rect.width - 0.5;
        const py = (event.clientY - rect.top) / rect.height - 0.5;

        tiltX(px * 5);
        tiltY(-py * 4);
        floats.forEach(({ depth, x }) => x(px * 18 * depth));
    };

    root.value.addEventListener('pointermove', onMove);

    return () => root.value?.removeEventListener('pointermove', onMove);
});
</script>

<template>
    <section ref="root" class="relative isolate overflow-hidden pt-28 pb-20 sm:pt-36 lg:pb-28">
        <!-- Background: fine grid, soft aurora, horizon light. -->
        <div data-hero-bg class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-grid [mask-image:radial-gradient(ellipse_70%_55%_at_50%_0%,black,transparent)]" />
            <div class="absolute top-[-18rem] left-1/2 h-[42rem] w-[72rem] -translate-x-1/2 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_32%,transparent),transparent)] opacity-60 blur-2xl dark:opacity-70" />
            <div class="absolute top-[22rem] left-[62%] h-[22rem] w-[36rem] -translate-x-1/2 rounded-full bg-[radial-gradient(closest-side,rgb(34_211_238/0.18),transparent)] blur-2xl" />
        </div>

        <div class="mx-auto max-w-7xl px-5 text-center sm:px-8">
            <Link
                data-hero-badge
                :href="route('features')"
                class="group mx-auto inline-flex items-center gap-2 rounded-full border border-line bg-surface/70 py-1 pr-3 pl-1 text-small text-ink-2 shadow-card backdrop-blur transition-colors hover:border-line-strong hover:text-ink"
            >
                <span class="inline-flex items-center gap-1 rounded-full bg-accent/12 px-2 py-0.5 text-caption font-medium text-accent-text"><Sparkles class="size-3" />New</span>
                Client portals with milestone approvals
                <ArrowRight class="size-3.5 transition-transform group-hover:translate-x-0.5" />
            </Link>

            <h1 class="mt-7 text-display text-balance text-ink uppercase">
                <span class="block">
                    <template v-for="(word, wordIndex) in headline[0]" :key="word">
                        <span class="inline-block overflow-hidden pb-[0.08em] align-bottom"><span data-hero-word class="inline-block">{{ word }}</span></span>{{ wordIndex < headline[0].length - 1 ? ' ' : '' }}
                    </template>
                </span>
                <!-- background-clip:text only paints the element's own text, so the gradient line animates as one unit. -->
                <span class="block overflow-hidden pb-[0.08em]"><span data-hero-word class="text-gradient inline-block">{{ headline[1].join(' ') }}</span></span>
            </h1>

            <p data-hero-sub class="mx-auto mt-7 max-w-2xl text-lead text-pretty text-ink-3 sm:text-[1.1875rem]">
                OrbitOps brings clients, projects, teams, time, invoices and business operations into one intelligent workspace.
            </p>

            <div data-hero-cta class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Button :href="route('register')" size="lg" :icon-right="ArrowRight" class="w-full sm:w-auto">Start Building</Button>
                <Button :href="route('features')" variant="secondary" size="lg" class="w-full sm:w-auto">Explore the Platform</Button>
            </div>
            <p v-if="demoEnabled" class="mt-4 text-small text-ink-3" data-hero-sub>
                Or
                <button type="button" class="inline-flex items-center gap-1 font-medium text-accent-text underline-offset-4 hover:underline disabled:opacity-60" :disabled="demoProcessing" @click="startDemo">
                    <Play class="size-3" />explore the live demo workspace
                </button>
                — no sign-up needed.
            </p>
        </div>

        <!-- Product stage -->
        <div ref="stage" data-hero-stage class="relative mx-auto mt-16 max-w-6xl px-4 sm:mt-20 sm:px-8 [perspective:2200px]">
            <div data-hero-tilt class="[transform-style:preserve-3d]">
                <div data-hero-mock class="relative origin-top [transform:rotateX(14deg)] [transform-style:preserve-3d]">
                    <div class="pointer-events-none absolute -inset-px -z-10 rounded-[20px] bg-[linear-gradient(180deg,color-mix(in_oklab,var(--accent)_45%,transparent),transparent_40%)] opacity-70 blur-xl" aria-hidden="true" />
                    <HeroDashboard />
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-canvas to-transparent" aria-hidden="true" />
                </div>
            </div>

            <!-- Floating UI cards -->
            <div data-hero-float data-depth="1.4" class="absolute top-[12%] -left-1 hidden w-72 rounded-xl border border-line bg-elevated/90 p-3.5 text-left shadow-overlay backdrop-blur-md sm:left-0 md:block lg:-left-6" aria-hidden="true">
                <div class="flex items-start gap-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-success/12"><CircleCheck class="size-4 text-success" /></span>
                    <div>
                        <p class="text-small font-semibold text-ink">Invoice ACM-1027 paid</p>
                        <p class="text-caption text-ink-3">Apex Commerce · just now</p>
                    </div>
                    <p class="ml-auto text-small font-semibold text-success tabular">$14,200</p>
                </div>
            </div>

            <div data-hero-float data-depth="2" class="absolute top-[4%] -right-1 hidden w-56 rounded-xl border border-line bg-elevated/90 p-3.5 text-left shadow-overlay backdrop-blur-md md:block lg:-right-8" aria-hidden="true">
                <p class="flex items-center gap-1.5 text-caption text-ink-3"><span class="size-1.5 animate-pulse rounded-full bg-danger" />Tracking · Website Redesign</p>
                <p class="mt-1.5 font-mono text-[1.625rem] leading-none font-medium tracking-tight text-ink tabular">{{ clock }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-caption text-ink-3">Build homepage sections</span>
                    <span class="rounded-md bg-danger/12 px-2 py-0.5 text-caption font-medium text-danger">Stop</span>
                </div>
            </div>

            <div data-hero-float data-depth="1" class="absolute right-[6%] bottom-[18%] hidden w-60 rounded-xl border border-line bg-elevated/90 p-3.5 text-left shadow-overlay backdrop-blur-md lg:block" aria-hidden="true">
                <div class="flex items-center justify-between">
                    <span class="rounded-full bg-success/12 px-2 py-0.5 text-caption font-medium text-success">Done</span>
                    <span class="text-caption text-ink-3">WEB-14</span>
                </div>
                <p class="mt-2 text-small font-semibold text-ink">Homepage Design</p>
                <div class="mt-2.5 flex items-center justify-between">
                    <div class="flex -space-x-1.5">
                        <span class="flex size-5 items-center justify-center rounded-full bg-sky-500/20 text-[8px] font-semibold text-sky-800 ring-2 ring-elevated dark:text-sky-300">SC</span>
                        <span class="flex size-5 items-center justify-center rounded-full bg-violet-500/20 text-[8px] font-semibold text-violet-800 ring-2 ring-elevated dark:text-violet-300">MR</span>
                    </div>
                    <span class="text-caption text-ink-3">3 comments · 2 files</span>
                </div>
            </div>

            <div data-hero-float data-depth="1.6" class="absolute bottom-[26%] left-[4%] hidden w-60 rounded-xl border border-line bg-elevated/90 p-3.5 text-left shadow-overlay backdrop-blur-md lg:block" aria-hidden="true">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-7 items-center justify-center rounded-full bg-amber-500/20 text-[10px] font-semibold text-amber-800 dark:text-amber-300">HB</span>
                    <div>
                        <p class="text-small text-ink"><span class="font-semibold">Hannah</span> approved</p>
                        <p class="text-caption text-ink-3">Wireframes · Client portal</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
