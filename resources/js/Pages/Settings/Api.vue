<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Copy, KeyRound, Plus, Trash2, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { formatDate, formatRelative } from '@/lib/format';

const props = defineProps({
    tokens: { type: Array, required: true },
    baseUrl: { type: String, required: true },
});

const page = usePage();
const creating = ref(false);
const form = useForm({ name: '', abilities: ['read'] });
const created = computed(() => page.flash?.token ?? null);

function submit() {
    form.post(route('settings.api.tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
}

async function copy(text, label = 'Copied') {
    await navigator.clipboard?.writeText(text);
    toast.success(label);
}

async function revoke(token) {
    if (await confirm({ title: `Revoke “${token.name}”?`, description: 'Apps using this token stop working immediately.', confirmLabel: 'Revoke token' })) {
        router.delete(route('settings.api.tokens.destroy', token.id), { preserveScroll: true });
    }
}

const example = computed(() => `curl ${props.baseUrl}/projects \\\n  -H "Authorization: Bearer YOUR_TOKEN" \\\n  -H "Accept: application/json"`);
</script>

<template>
    <Head title="API" />
    <div class="space-y-6">
        <div v-if="created" class="rounded-xl border border-success/30 bg-success/8 p-5">
            <p class="flex items-center gap-2 text-body font-medium text-ink"><KeyRound class="size-4 text-success" />“{{ created.name }}” is ready</p>
            <p class="mt-1 flex items-center gap-1.5 text-small text-ink-2"><TriangleAlert class="size-4 text-warning" />Copy it now. For your security it won't be shown again.</p>
            <div class="mt-3 flex items-center gap-2">
                <code class="min-w-0 flex-1 truncate rounded-lg border border-line bg-surface px-3 py-2 font-mono text-small text-ink">{{ created.plain }}</code>
                <Button variant="secondary" :icon="Copy" @click="copy(created.plain, 'Token copied')">Copy</Button>
            </div>
        </div>

        <SettingsSection title="Personal access tokens" description="Tokens act as you, inside this workspace only, and can never do more than your role allows.">
            <ul v-if="tokens.length" class="divide-y divide-line">
                <li v-for="token in tokens" :key="token.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-subtle text-ink-3"><KeyRound class="size-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-body text-ink">{{ token.name }} <Badge v-for="ability in token.abilities" :key="ability" :tone="ability === 'write' ? 'warning' : 'neutral'" size="sm">{{ ability }}</Badge></p>
                        <p class="text-caption text-ink-3">Created {{ formatDate(token.created_at) }} · {{ token.last_used_at ? `last used ${formatRelative(token.last_used_at)}` : 'never used' }}</p>
                    </div>
                    <Button variant="ghost" size="sm" square :icon="Trash2" :aria-label="`Revoke ${token.name}`" @click="revoke(token)" />
                </li>
            </ul>
            <EmptyState v-else :icon="KeyRound" title="No tokens yet" description="Create a token to read projects, tasks and invoices from scripts or other tools." compact />
            <template #footer>
                <Button :icon="Plus" @click="creating = true">New token</Button>
            </template>
        </SettingsSection>

        <SettingsSection title="Quick start" description="A JSON REST API, versioned under /api/v1.">
            <div class="relative">
                <pre class="overflow-x-auto rounded-lg bg-[#0b0b10] p-4 font-mono text-small leading-relaxed text-[#e6e6eb]">{{ example }}</pre>
                <Button variant="ghost" size="sm" square :icon="Copy" aria-label="Copy example" class="absolute top-2 right-2 text-[#9a9aa8] hover:text-white" @click="copy(example, 'Example copied')" />
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-2 font-mono text-small sm:grid-cols-2">
                <div v-for="[method, path] in [['GET', '/me'], ['GET', '/projects'], ['GET', '/projects/{id}'], ['GET', '/clients'], ['GET', '/tasks'], ['POST', '/tasks'], ['PATCH', '/tasks/{id}'], ['GET', '/time-entries'], ['GET', '/invoices']]" :key="method + path" class="flex gap-2">
                    <dt class="w-12 shrink-0" :class="method === 'GET' ? 'text-info' : 'text-warning'">{{ method }}</dt>
                    <dd class="text-ink-2">{{ path }}</dd>
                </div>
            </dl>
        </SettingsSection>
    </div>

    <Modal v-model:open="creating" title="New API token" description="Give it a name you'll recognise, like the app or script that uses it." size="sm">
        <form id="token-form" class="space-y-4" novalidate @submit.prevent="submit">
            <Field label="Token name" :error="form.errors.name" required v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.name" placeholder="e.g. Reporting script" :invalid="invalid" />
            </Field>
            <fieldset>
                <legend class="mb-2 text-label text-ink-2">Permissions</legend>
                <div class="space-y-2">
                    <Checkbox v-model="form.abilities" value="read" label="Read" description="List and view projects, tasks, clients, time and invoices." />
                    <Checkbox v-model="form.abilities" value="write" label="Write" description="Create and update tasks." />
                </div>
                <p v-if="form.errors.abilities" class="mt-1.5 text-small text-danger">{{ form.errors.abilities }}</p>
            </fieldset>
        </form>
        <template #footer>
            <Button variant="secondary" @click="creating = false">Cancel</Button>
            <Button type="submit" form="token-form" :loading="form.processing" :disabled="!form.name || !form.abilities.length">Create token</Button>
        </template>
    </Modal>
</template>
