<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Info } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import { formatBytes, formatMoney } from '@/lib/format';

const props = defineProps({
    plans: { type: Array, required: true },
    current: { type: Object, required: true },
    usage: { type: Object, required: true },
});

const cycle = ref(props.current.cycle);
const form = useForm({ plan: props.current.plan, cycle: props.current.cycle });
const currentPlan = computed(() => props.plans.find((plan) => plan.key === props.current.plan));

const meters = computed(() => {
    const limits = currentPlan.value.limits;
    return [
        { label: 'Team seats', used: props.usage.members, limit: limits.members, format: (value) => value },
        { label: 'Active projects', used: props.usage.projects, limit: limits.projects, format: (value) => value },
        { label: 'File storage', used: props.usage.storage_bytes, limit: limits.storage_gb * 1024 ** 3, format: formatBytes, limitLabel: `${limits.storage_gb} GB` },
    ];
});

const price = (plan) => (cycle.value === 'yearly' ? plan.yearly : plan.monthly);

function choose(plan) {
    form.plan = plan.key;
    form.cycle = cycle.value;
    form.put(route('settings.billing.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Billing" />
    <div class="space-y-6">
        <SettingsSection title="Current plan" :description="`${currentPlan.name} · billed ${current.cycle}`">
            <div class="grid gap-5 sm:grid-cols-3">
                <div v-for="meter in meters" :key="meter.label">
                    <p class="mb-1.5 flex justify-between text-small"><span class="text-ink-3">{{ meter.label }}</span><span class="font-medium text-ink tabular">{{ meter.format(meter.used) }} / {{ meter.limit === null ? '∞' : (meter.limitLabel ?? meter.limit) }}</span></p>
                    <ProgressBar :value="meter.limit ? Math.min(100, (meter.used / meter.limit) * 100) : 4" :tone="meter.limit && meter.used / meter.limit > 0.9 ? 'warning' : 'accent'" size="sm" :label="meter.label" />
                </div>
            </div>
            <p class="mt-5 flex items-start gap-2 rounded-lg bg-subtle px-3 py-2.5 text-small text-ink-2">
                <Info class="mt-0.5 size-4 shrink-0 text-ink-3" />
                No payment provider is connected in this build, so plan changes apply instantly and no card is charged. Connect Stripe to bill for real.
            </p>
        </SettingsSection>

        <SettingsSection title="Plans" description="Prices are per workspace. Switch any time.">
            <div class="mb-5 flex justify-center">
                <SegmentedControl v-model="cycle" :options="[{ value: 'monthly', label: 'Monthly' }, { value: 'yearly', label: 'Yearly · save ~17%' }]" label="Billing cycle" />
            </div>
            <p v-if="form.errors.plan" class="mb-4 rounded-lg bg-danger/10 px-3 py-2.5 text-small text-danger" role="alert">{{ form.errors.plan }}</p>
            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="plan in plans"
                    :key="plan.key"
                    class="relative flex flex-col rounded-xl border p-5"
                    :class="plan.key === current.plan ? 'border-accent ring-1 ring-accent' : 'border-line'"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-h3 text-ink">{{ plan.name }}</h3>
                        <Badge v-if="plan.key === current.plan" tone="accent" size="sm">Current</Badge>
                        <Badge v-else-if="plan.recommended" tone="info" size="sm">Popular</Badge>
                    </div>
                    <p class="mt-1 text-small text-ink-3">{{ plan.tagline }}</p>
                    <p class="mt-4 flex items-baseline gap-1">
                        <span class="text-[2rem] leading-none font-semibold tracking-tight text-ink">{{ price(plan) === 0 ? 'Free' : formatMoney(price(plan), 'USD', { decimals: 0 }) }}</span>
                        <span v-if="price(plan) > 0" class="text-small text-ink-3">/ month{{ cycle === 'yearly' ? ', billed yearly' : '' }}</span>
                    </p>
                    <ul class="mt-4 flex-1 space-y-2 text-small text-ink-2">
                        <li v-for="feature in plan.features" :key="feature" class="flex gap-2"><Check class="mt-0.5 size-4 shrink-0 text-success" />{{ feature }}</li>
                    </ul>
                    <Button
                        class="mt-5"
                        block
                        :variant="plan.key === current.plan && cycle === current.cycle ? 'secondary' : 'primary'"
                        :disabled="(plan.key === current.plan && cycle === current.cycle) || form.processing"
                        :loading="form.processing && form.plan === plan.key"
                        @click="choose(plan)"
                    >
                        {{ plan.key === current.plan ? (cycle === current.cycle ? 'Current plan' : `Switch to ${cycle}`) : `Switch to ${plan.name}` }}
                    </Button>
                </article>
            </div>
        </SettingsSection>
    </div>
</template>
