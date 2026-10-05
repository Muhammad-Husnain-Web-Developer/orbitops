<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { Camera, Info } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    timezones: { type: Array, default: () => [] },
    showTitle: { type: Boolean, default: true },
});

const page = usePage();
const form = useForm({ name: props.profile.name, email: props.profile.email, title: props.profile.title ?? '', timezone: props.profile.timezone, avatar: null, remove_avatar: false });
const preview = ref(null);
const input = ref(null);
const shown = computed(() => (form.remove_avatar ? null : (preview.value ?? props.profile.avatar_url)));
const isDemo = computed(() => page.props.auth.user.is_demo);

function pick(event) {
    const [file] = event.target.files ?? [];
    if (!file) return;
    form.avatar = file;
    form.remove_avatar = false;
    preview.value = URL.createObjectURL(file);
}

function removeAvatar() {
    form.avatar = null;
    form.remove_avatar = true;
    preview.value = null;
    if (input.value) input.value.value = '';
}

function submit() {
    form.post(route('settings.general.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.avatar = null;
            form.remove_avatar = false;
            preview.value = null;
            form.defaults();
        },
    });
}
</script>

<template>
    <SettingsSection title="Profile" description="How you appear to teammates and clients.">
        <form id="profile-form" class="space-y-5" novalidate @submit.prevent="submit">
            <div class="flex items-center gap-4">
                <Avatar :user="{ ...profile, avatar_url: shown }" :name="form.name" size="xl" decorative />
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-md border border-line bg-surface px-3 text-small font-medium text-ink shadow-card hover:bg-hover has-focus-visible:outline-2 has-focus-visible:outline-accent">
                        <Camera class="size-4" />Upload photo
                        <input ref="input" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick" />
                    </label>
                    <Button v-if="shown" variant="ghost" size="sm" @click="removeAvatar">Remove</Button>
                </div>
            </div>
            <p v-if="form.errors.avatar" class="-mt-3 text-small text-danger" role="alert">{{ form.errors.avatar }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Full name" :error="form.errors.name" required v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.name" autocomplete="name" :invalid="invalid" />
                </Field>
                <Field label="Email" :error="form.errors.email" required :hint="isDemo ? 'The demo account email can’t be changed.' : 'Changing it asks you to verify the new address.'" v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.email" type="email" autocomplete="email" :readonly="isDemo" :invalid="invalid" />
                </Field>
                <Field v-if="showTitle" label="Job title" :error="form.errors.title" v-slot="{ id, invalid }">
                    <Input :id="id" v-model="form.title" placeholder="e.g. Lead Designer" :invalid="invalid" />
                </Field>
                <Field label="Time zone" :error="form.errors.timezone" v-slot="{ id, invalid }">
                    <Select :id="id" v-model="form.timezone" :options="timezones" :invalid="invalid" />
                </Field>
            </div>
            <p v-if="!profile.email_verified" class="flex items-center gap-2 rounded-lg bg-warning/10 px-3 py-2 text-small text-warning"><Info class="size-4 shrink-0" />Your email address isn't verified yet. Check your inbox for the link.</p>
        </form>
        <template #footer>
            <Button type="submit" form="profile-form" :loading="form.processing" :disabled="!form.isDirty">Save profile</Button>
        </template>
    </SettingsSection>
</template>
