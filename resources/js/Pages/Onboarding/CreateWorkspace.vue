<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, Check } from '@lucide/vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import { swatch } from '@/lib/colors';

defineProps({
    accents: { type: Array, required: true },
});

const form = useForm({ name: '', industry: '', accent: 'violet' });

const industries = ['Creative Agency', 'Design Studio', 'Software Company', 'Consultancy', 'Marketing Agency', 'Freelance', 'Other'].map((value) => ({ value, label: value }));

function submit() {
    form.post(route('onboarding.store'));
}
</script>

<template>
    <Head title="Set up your workspace" />
    <p class="text-eyebrow text-accent-text uppercase">Step 1 of 1</p>
    <h1 class="mt-3 text-h2 text-ink">Set up your workspace</h1>
    <p class="mt-1.5 text-body text-ink-3">A workspace holds your team, clients, projects and finances. You can create more later.</p>
    <form class="mt-8 space-y-5" novalidate @submit.prevent="submit">
        <Field label="Workspace name" :error="form.errors.name" v-slot="{ id, invalid, describedby }">
            <Input :id="id" v-model="form.name" autofocus required :invalid="invalid" :aria-describedby="describedby" placeholder="Acme Studio" />
        </Field>
        <Field label="What kind of business is it?" :error="form.errors.industry" optional v-slot="{ id, invalid }">
            <Select :id="id" v-model="form.industry" :options="industries" placeholder="Choose one…" :invalid="invalid" />
        </Field>
        <fieldset>
            <legend class="text-label text-ink">Accent colour</legend>
            <p class="mt-0.5 text-small text-ink-3">Helps you tell workspaces apart at a glance.</p>
            <div class="mt-3 flex gap-2.5">
                <label v-for="accent in accents" :key="accent" class="relative cursor-pointer">
                    <input v-model="form.accent" type="radio" name="accent" :value="accent" class="peer sr-only" :aria-label="accent" />
                    <span class="flex size-9 items-center justify-center rounded-full ring-offset-2 ring-offset-canvas transition peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-accent" :class="swatch(accent)">
                        <Check v-if="form.accent === accent" class="size-4 text-white" />
                    </span>
                </label>
            </div>
        </fieldset>
        <Button type="submit" size="lg" block :loading="form.processing" :icon-right="ArrowRight">Create workspace</Button>
    </form>
</template>
