<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import ColorPicker from '@/Components/UI/ColorPicker.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    accents: { type: Array, required: true },
    currencies: { type: Array, required: true },
    timezones: { type: Array, default: () => [] },
});

const form = useForm({ ...props.settings, logo: null, remove_logo: false });
const preview = ref(null);
const input = ref(null);
const logo = computed(() => (form.remove_logo ? null : (preview.value ?? props.settings.logo_url)));
const currencyOptions = computed(() => props.currencies.map((code) => ({ value: code, label: code })));

function pick(event) {
    const [file] = event.target.files ?? [];
    if (!file) return;
    form.logo = file;
    form.remove_logo = false;
    preview.value = URL.createObjectURL(file);
}

function removeLogo() {
    form.logo = null;
    form.remove_logo = true;
    preview.value = null;
    if (input.value) input.value.value = '';
}

function submit() {
    form.transform(({ logo_url, initials, ...data }) => data).post(route('settings.workspace.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.logo = null;
            form.remove_logo = false;
            preview.value = null;
            form.defaults();
        },
    });
}
</script>

<template>
    <Head title="Workspace settings" />
    <form class="space-y-6" novalidate @submit.prevent="submit">
        <SettingsSection title="Brand" description="Shown in the sidebar, on invoices and in your clients' portal.">
            <div class="space-y-5">
                <div class="flex items-center gap-4">
                    <Avatar :user="{ name: form.name, initials: settings.initials, logo_url: logo }" :name="form.name" square size="xl" decorative />
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-md border border-line bg-surface px-3 text-small font-medium text-ink shadow-card hover:bg-hover has-focus-visible:outline-2 has-focus-visible:outline-accent">
                            <ImagePlus class="size-4" />Upload logo
                            <input ref="input" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick" />
                        </label>
                        <Button v-if="logo" variant="ghost" size="sm" @click="removeLogo">Remove</Button>
                    </div>
                </div>
                <p v-if="form.errors.logo" class="-mt-3 text-small text-danger" role="alert">{{ form.errors.logo }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Workspace name" :error="form.errors.name" required v-slot="{ id, invalid }">
                        <Input :id="id" v-model="form.name" :invalid="invalid" />
                    </Field>
                    <Field label="Industry" :error="form.errors.industry" v-slot="{ id, invalid }">
                        <Input :id="id" v-model="form.industry" placeholder="e.g. Creative agency" :invalid="invalid" />
                    </Field>
                </div>
                <Field label="Accent colour" :error="form.errors.accent" hint="Recolours buttons, highlights and charts for everyone in this workspace.">
                    <ColorPicker v-model="form.accent" label="Accent colour" />
                </Field>
            </div>
        </SettingsSection>

        <SettingsSection title="Regional & invoicing" description="Defaults for new invoices. Existing invoices keep their own values.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Currency" :error="form.errors.currency" v-slot="{ id, invalid }">
                    <Select :id="id" v-model="form.currency" :options="currencyOptions" :invalid="invalid" />
                </Field>
                <Field label="Time zone" :error="form.errors.timezone" v-slot="{ id, invalid }">
                    <Select :id="id" v-model="form.timezone" :options="timezones" :invalid="invalid" />
                </Field>
                <Field label="Invoice number prefix" :error="form.errors.invoice_prefix" :hint="`Next invoices look like ${(form.invoice_prefix || 'INV').toUpperCase()}-1042.`" v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.invoice_prefix" maxlength="6" class="uppercase" :invalid="invalid" />
                </Field>
                <Field label="Default tax rate" :error="form.errors.default_tax_rate" v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.default_tax_rate" type="number" min="0" max="100" step="0.01" suffix="%" :invalid="invalid" />
                </Field>
                <Field label="Payment terms" :error="form.errors.payment_terms" hint="Days until an invoice is due." v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.payment_terms" type="number" min="0" max="120" suffix="days" :invalid="invalid" />
                </Field>
            </div>
            <template #footer>
                <Button type="submit" :loading="form.processing" :disabled="!form.isDirty">Save workspace</Button>
            </template>
        </SettingsSection>
    </form>
</template>
