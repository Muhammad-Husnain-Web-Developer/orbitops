<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Bell, Mail } from '@lucide/vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Button from '@/Components/UI/Button.vue';
import Switch from '@/Components/UI/Switch.vue';

const props = defineProps({
    types: { type: Array, required: true },
    preferences: { type: Object, required: true },
});

const form = useForm({ preferences: JSON.parse(JSON.stringify(props.preferences)) });

function submit() {
    form.put(route('settings.notifications.update'), { preserveScroll: true, onSuccess: () => form.defaults() });
}
</script>

<template>
    <Head title="Notification settings" />
    <SettingsSection title="Notifications" description="Choose what reaches you in the app and by email. Realtime alerts follow the in-app setting.">
        <div class="-mx-5 overflow-x-auto sm:-mx-6">
            <table class="w-full min-w-[480px] text-left">
                <caption class="sr-only">Notification preferences by type and channel</caption>
                <thead>
                    <tr class="border-b border-line text-caption text-ink-3">
                        <th scope="col" class="px-5 pb-3 font-medium sm:px-6">Activity</th>
                        <th scope="col" class="w-24 px-3 pb-3 text-center font-medium"><span class="inline-flex items-center gap-1"><Bell class="size-3.5" />In-app</span></th>
                        <th scope="col" class="w-24 px-5 pb-3 text-center font-medium sm:px-6"><span class="inline-flex items-center gap-1"><Mail class="size-3.5" />Email</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="type in types" :key="type.key">
                        <th scope="row" class="px-5 py-3.5 font-normal sm:px-6">
                            <p class="text-body text-ink">{{ type.label }}</p>
                            <p class="text-small text-ink-3">{{ type.description }}</p>
                        </th>
                        <td class="px-3 py-3.5"><div class="flex justify-center"><Switch v-model="form.preferences[type.key].database" :aria-label="`${type.label} in the app`" /></div></td>
                        <td class="px-5 py-3.5 sm:px-6"><div class="flex justify-center"><Switch v-model="form.preferences[type.key].mail" :aria-label="`${type.label} by email`" /></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <template #footer>
            <Button :loading="form.processing" :disabled="!form.isDirty" @click="submit">Save preferences</Button>
        </template>
    </SettingsSection>
</template>
