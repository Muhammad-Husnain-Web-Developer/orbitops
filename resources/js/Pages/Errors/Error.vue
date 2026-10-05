<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, RotateCw } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Logo from '@/Components/UI/Logo.vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const page = usePage();

const copy = {
    403: ['Access denied', "You don't have clearance for this orbit.", 'Your role in this workspace does not allow this. Ask a workspace admin if you think you should have access.'],
    404: ['Page not found', "Looks like this orbit\ndoesn't exist.", 'The page may have moved, or the link is out of date. Let us get you back on course.'],
    419: ['Session expired', 'Your session drifted off.', 'For your security the page expired. Refresh and try again.'],
    429: ['Too many requests', 'Slow down, astronaut.', 'You are moving faster than our rate limits allow. Wait a moment, then try again.'],
    500: ['Server error', 'Something went wrong.', 'Our systems hit a turbulence. The team has been notified — please try again.'],
    503: ['Maintenance', 'Back in a moment.', 'OrbitOps is undergoing scheduled maintenance. Please check back shortly.'],
};

const content = computed(() => copy[props.status] ?? copy[500]);
const signedIn = computed(() => Boolean(page.props.auth?.user));
const isClient = computed(() => Boolean(page.props.access?.is_client));
const homeHref = computed(() => (!signedIn.value ? route('home') : isClient.value ? route('portal.dashboard') : route('dashboard')));
const retryable = computed(() => [419, 429, 500, 503].includes(props.status));

function retry() {
    window.location.reload();
}
</script>

<template>
    <Head :title="content[0]" />
    <div class="relative isolate flex min-h-dvh flex-col overflow-hidden bg-canvas text-ink">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-grid [mask-image:radial-gradient(ellipse_60%_50%_at_50%_45%,black,transparent)]" />
            <div class="absolute top-1/2 left-1/2 size-[40rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_20%,transparent),transparent)] blur-2xl" />
        </div>

        <header class="px-6 py-5 sm:px-10">
            <Link :href="route('home')" aria-label="OrbitOps home"><Logo /></Link>
        </header>

        <main class="flex flex-1 flex-col items-center justify-center px-6 pb-20 text-center">
            <div class="relative mb-2" aria-hidden="true">
                <p class="text-[clamp(7rem,22vw,13rem)] leading-none font-semibold tracking-[-0.07em] text-ink/10 tabular">{{ status }}</p>
                <!-- A planet with its ring and a lost satellite. -->
                <svg viewBox="0 0 200 200" class="error-float absolute top-1/2 left-1/2 size-36 -translate-x-1/2 -translate-y-1/2 sm:size-44">
                    <defs>
                        <linearGradient id="planet" x1="40" y1="40" x2="160" y2="160" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#a78bfa" />
                            <stop offset="1" stop-color="#4c3fd6" />
                        </linearGradient>
                    </defs>
                    <circle cx="100" cy="100" r="44" fill="url(#planet)" />
                    <ellipse cx="100" cy="100" rx="82" ry="22" transform="rotate(-20 100 100)" stroke="#c4b5fd" stroke-opacity="0.7" stroke-width="3" fill="none" />
                    <circle cx="172" cy="58" r="6" fill="#22d3ee" />
                </svg>
            </div>
            <p class="text-eyebrow text-accent-text uppercase">{{ content[0] }}</p>
            <h1 class="mt-3 text-h1 whitespace-pre-line text-ink">{{ content[1] }}</h1>
            <p class="mt-4 max-w-md text-lead text-ink-3">{{ content[2] }}</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <Button v-if="retryable" size="lg" :icon="RotateCw" @click="retry">Try Again</Button>
                <Button :href="homeHref" :variant="retryable ? 'secondary' : 'primary'" size="lg" :icon="ArrowLeft">
                    {{ signedIn ? 'Back to Dashboard' : 'Back to home' }}
                </Button>
            </div>
        </main>
    </div>
</template>

<style scoped>
.error-float {
    animation: drift 7s ease-in-out infinite;
}

/* Centring comes from Tailwind's `translate` property; the keyframes only add drift. */
@keyframes drift {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-6%) rotate(4deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .error-float {
        animation: none;
    }
}
</style>
