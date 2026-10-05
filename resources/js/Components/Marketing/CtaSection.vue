<script setup>
import { router } from '@inertiajs/vue3';
import { ArrowRight, Play } from '@lucide/vue';
import { ref } from 'vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    demoEnabled: { type: Boolean, default: false },
    title: { type: String, default: 'Put your business in orbit.' },
});

const processing = ref(false);

function startDemo() {
    router.post(route('demo.login'), {}, { onStart: () => (processing.value = true), onFinish: () => (processing.value = false) });
}
</script>

<template>
    <section class="relative isolate overflow-hidden border-t border-line py-24 sm:py-32">
        <div class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center" aria-hidden="true">
            <svg viewBox="0 0 800 800" class="orbit-spin size-[52rem] max-w-none text-line" fill="none">
                <circle cx="400" cy="400" r="390" stroke="currentColor" stroke-dasharray="2 8" />
                <circle cx="400" cy="400" r="290" stroke="currentColor" />
                <circle cx="400" cy="400" r="190" stroke="currentColor" stroke-dasharray="1 6" />
                <circle cx="690" cy="400" r="6" class="fill-accent" />
                <circle cx="400" cy="10" r="4" fill="#22d3ee" />
                <circle cx="210" cy="400" r="3" fill="currentColor" />
            </svg>
            <div class="absolute size-[30rem] rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--accent)_22%,transparent),transparent)] blur-2xl" />
        </div>
        <div class="mx-auto max-w-3xl px-5 text-center sm:px-8">
            <h2 data-reveal class="text-[clamp(2.5rem,1.6rem+4vw,4.75rem)] leading-[0.98] font-semibold tracking-[-0.045em] text-balance text-ink">{{ title }}</h2>
            <p data-reveal class="mx-auto mt-6 max-w-xl text-lead text-ink-3">Set up your workspace in minutes. Bring your team, your clients and your first project — and leave the spreadsheets behind.</p>
            <div data-reveal class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Button :href="route('register')" size="lg" :icon-right="ArrowRight" class="w-full sm:w-auto">Start Building</Button>
                <Button v-if="demoEnabled" variant="secondary" size="lg" :icon="Play" :loading="processing" class="w-full sm:w-auto" @click="startDemo">Try the live demo</Button>
                <Button v-else :href="route('contact')" variant="secondary" size="lg" class="w-full sm:w-auto">Talk to us</Button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.orbit-spin {
    animation: orbit 90s linear infinite;
}

@keyframes orbit {
    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .orbit-spin {
        animation: none;
    }
}
</style>
