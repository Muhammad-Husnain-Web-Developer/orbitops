<script setup>
import { Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import { useCountUp } from '@/composables/useCountUp';

const props = defineProps({
    plans: { type: Object, required: true },
});

const cycle = ref('yearly');
const entries = computed(() => Object.entries(props.plans).map(([key, plan]) => ({ key, ...plan })));

const prices = Object.fromEntries(Object.keys(props.plans).map((key) => [key, useCountUp(() => props.plans[key][cycle.value], { duration: 500 })]));
</script>

<template>
    <div>
        <div class="flex justify-center">
            <SegmentedControl
                v-model="cycle"
                label="Billing cycle"
                :options="[
                    { value: 'monthly', label: 'Monthly' },
                    { value: 'yearly', label: 'Yearly', badge: '-17%' },
                ]"
            />
        </div>

        <div class="mt-10 grid gap-4 lg:grid-cols-3 lg:items-stretch">
            <article
                v-for="plan in entries"
                :key="plan.key"
                data-reveal
                class="relative flex flex-col rounded-2xl border p-6 transition-[border-color,transform,box-shadow] duration-300 hover:-translate-y-1 sm:p-7"
                :class="plan.recommended ? 'border-accent/50 bg-surface shadow-[0_0_0_1px_color-mix(in_oklab,var(--accent)_30%,transparent),0_30px_80px_-30px_color-mix(in_oklab,var(--accent)_45%,transparent)]' : 'border-line bg-surface shadow-card hover:border-line-strong hover:shadow-raised'"
            >
                <span v-if="plan.recommended" class="absolute -top-3 left-6 rounded-full bg-accent px-2.5 py-1 text-caption font-semibold text-accent-ink shadow-raised">Recommended</span>
                <h3 class="text-h3 text-ink">{{ plan.name }}</h3>
                <p class="mt-1 text-body text-ink-3">{{ plan.tagline }}</p>
                <p class="mt-6 flex items-baseline gap-1.5">
                    <span class="text-[2.75rem] leading-none font-semibold tracking-[-0.04em] text-ink tabular">${{ Math.round(prices[plan.key].value) }}</span>
                    <span class="text-body text-ink-3">{{ plan.monthly ? 'per seat / month' : 'forever' }}</span>
                </p>
                <p class="mt-1.5 h-5 text-small text-ink-3">
                    <template v-if="plan.monthly && cycle === 'yearly'">Billed annually</template>
                    <template v-else-if="plan.monthly">Billed monthly</template>
                </p>
                <Button :href="route('register', { plan: plan.key })" :variant="plan.recommended ? 'primary' : 'secondary'" size="lg" block class="mt-6">
                    {{ plan.monthly ? `Start with ${plan.name}` : 'Start for free' }}
                </Button>
                <ul class="mt-7 space-y-3 border-t border-line pt-6">
                    <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-body text-ink-2">
                        <Check class="mt-0.5 size-4 shrink-0 text-accent-text" aria-hidden="true" />
                        {{ feature }}
                    </li>
                </ul>
            </article>
        </div>
        <p class="mt-8 text-center text-small text-ink-3">Prices are illustrative placeholders while OrbitOps is in early access. No card is required to start.</p>
    </div>
</template>
