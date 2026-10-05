<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Copy, Download, KeyRound, ShieldCheck, ShieldOff } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { api, apiError } from '@/lib/api';

const props = defineProps({
    twoFactor: { type: Object, required: true },
    reload: { type: Array, default: () => ['two_factor'] },
});

const page = usePage();
const isDemo = computed(() => page.props.auth.user.is_demo);

// Setup state lives here until the code is confirmed.
const setup = ref(null); // { svg, secret }
const code = ref('');
const codeError = ref(null);
const recoveryCodes = ref([]);
const busy = ref(false);

// Fortify asks for the password again before security changes; do it inline.
const passwordOpen = ref(false);
const password = ref('');
const passwordError = ref(null);
let pendingAction = null;

async function withConfirmedPassword(action) {
    try {
        const { confirmed } = await api.get(route('password.confirmation'));
        if (confirmed) return action();
    } catch {
        // Fall through to asking.
    }

    pendingAction = action;
    password.value = '';
    passwordError.value = null;
    passwordOpen.value = true;
}

async function submitPassword() {
    busy.value = true;
    try {
        await api.post(route('password.confirm.store'), { password: password.value });
        passwordOpen.value = false;
        await pendingAction?.();
    } catch (error) {
        passwordError.value = apiError(error).errors.password ?? apiError(error).message;
    } finally {
        busy.value = false;
    }
}

const fail = (error) => toast.error(apiError(error).message);

function enable() {
    withConfirmedPassword(async () => {
        busy.value = true;
        try {
            await api.post(route('two-factor.enable'));
            const [{ svg }, { secretKey }] = await Promise.all([api.get(route('two-factor.qr-code')), api.get(route('two-factor.secret-key'))]);
            setup.value = { svg, secret: secretKey };
            code.value = '';
            codeError.value = null;
        } catch (error) {
            fail(error);
        } finally {
            busy.value = false;
        }
    });
}

async function confirmCode() {
    busy.value = true;
    codeError.value = null;
    try {
        await api.post(route('two-factor.confirm'), { code: code.value.replace(/\s/g, '') });
        recoveryCodes.value = await api.get(route('two-factor.recovery-codes'));
        setup.value = null;
        toast.success('Two-factor authentication is on', { description: 'Save your recovery codes somewhere safe.' });
        router.reload({ only: [...props.reload, 'auth'] });
    } catch (error) {
        codeError.value = apiError(error).errors.code ?? apiError(error).message;
    } finally {
        busy.value = false;
    }
}

function showCodes() {
    withConfirmedPassword(async () => {
        try {
            recoveryCodes.value = await api.get(route('two-factor.recovery-codes'));
        } catch (error) {
            fail(error);
        }
    });
}

function regenerate() {
    withConfirmedPassword(async () => {
        try {
            await api.post(route('two-factor.regenerate-recovery-codes'));
            recoveryCodes.value = await api.get(route('two-factor.recovery-codes'));
            toast.success('New recovery codes generated', { description: 'The old codes no longer work.' });
        } catch (error) {
            fail(error);
        }
    });
}

async function disable() {
    if (!(await confirm({ title: 'Turn off two-factor authentication?', description: 'Your account will be protected by your password alone.', confirmLabel: 'Turn off' }))) return;

    withConfirmedPassword(async () => {
        try {
            await api.delete(route('two-factor.disable'));
            setup.value = null;
            recoveryCodes.value = [];
            toast('Two-factor authentication is off', { type: 'info' });
            router.reload({ only: [...props.reload, 'auth'] });
        } catch (error) {
            fail(error);
        }
    });
}

async function copyCodes() {
    await navigator.clipboard?.writeText(recoveryCodes.value.join('\n'));
    toast.success('Recovery codes copied');
}

function downloadCodes() {
    const blob = new Blob([`OrbitOps recovery codes for ${page.props.auth.user.email}\n\n${recoveryCodes.value.join('\n')}\n`], { type: 'text/plain' });
    const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'orbitops-recovery-codes.txt' });
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

<template>
    <SettingsSection title="Two-factor authentication" description="Ask for a code from an authenticator app when signing in.">
        <template v-if="setup">
            <ol class="space-y-5">
                <li>
                    <p class="text-body font-medium text-ink">1. Scan this QR code</p>
                    <p class="mt-0.5 text-small text-ink-3">Use 1Password, Google Authenticator, Authy or any TOTP app.</p>
                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <!-- Fortify renders the QR code as SVG markup from the server. -->
                        <div class="inline-flex rounded-xl bg-white p-3 shadow-card" aria-label="Two-factor QR code" role="img" v-html="setup.svg" />
                        <div class="text-small text-ink-3">
                            <p>Can't scan it? Enter this key instead:</p>
                            <code class="mt-1 block rounded-md bg-subtle px-2 py-1 font-mono text-small break-all text-ink">{{ setup.secret }}</code>
                        </div>
                    </div>
                </li>
                <li>
                    <p class="text-body font-medium text-ink">2. Enter the 6-digit code</p>
                    <form class="mt-3 flex max-w-sm items-start gap-2" @submit.prevent="confirmCode">
                        <Field label="Authentication code" :error="codeError" class="flex-1" v-slot="{ id, invalid }">
                            <Input :id="id" v-model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="123 456" class="font-mono" :invalid="invalid" />
                        </Field>
                        <Button type="submit" class="mt-6" :loading="busy" :disabled="code.replace(/\s/g, '').length < 6">Confirm</Button>
                    </form>
                </li>
            </ol>
        </template>

        <div v-else class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl" :class="twoFactor.confirmed ? 'bg-success/10 text-success' : 'bg-subtle text-ink-3'">
                    <component :is="twoFactor.confirmed ? ShieldCheck : ShieldOff" class="size-5" />
                </span>
                <div>
                    <p class="flex items-center gap-2 text-body font-medium text-ink">
                        {{ twoFactor.confirmed ? 'Enabled' : 'Not enabled' }}
                        <Badge v-if="twoFactor.confirmed" tone="success" size="sm">Protected</Badge>
                    </p>
                    <p class="text-small text-ink-3">{{ twoFactor.confirmed ? 'A code is required each time you sign in on a new device.' : 'Add a second step to keep your account safe even if your password leaks.' }}</p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <template v-if="twoFactor.confirmed">
                    <Button variant="secondary" size="sm" :icon="KeyRound" @click="showCodes">Recovery codes</Button>
                    <Button variant="danger-ghost" size="sm" :disabled="isDemo" @click="disable">Turn off</Button>
                </template>
                <Button v-else :icon="ShieldCheck" :loading="busy" :disabled="isDemo" @click="enable">Enable two-factor</Button>
            </div>
        </div>

        <div v-if="recoveryCodes.length" class="mt-5 rounded-xl border border-line bg-canvas/40 p-4">
            <p class="text-body font-medium text-ink">Recovery codes</p>
            <p class="mt-0.5 text-small text-ink-3">Each code works once if you lose your device. Store them in a password manager.</p>
            <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 font-mono text-small text-ink sm:grid-cols-4">
                <li v-for="item in recoveryCodes" :key="item">{{ item }}</li>
            </ul>
            <div class="mt-4 flex flex-wrap gap-2">
                <Button variant="secondary" size="sm" :icon="Copy" @click="copyCodes">Copy</Button>
                <Button variant="secondary" size="sm" :icon="Download" @click="downloadCodes">Download</Button>
                <Button variant="ghost" size="sm" :disabled="isDemo" @click="regenerate">Generate new codes</Button>
            </div>
        </div>

        <p v-if="isDemo" class="mt-4 text-small text-ink-3">Two-factor changes are disabled for the shared demo account.</p>

        <Modal v-model:open="passwordOpen" title="Confirm your password" description="For your security, confirm your password to continue." size="sm">
            <form id="confirm-password-form" novalidate @submit.prevent="submitPassword">
                <Field label="Password" :error="passwordError" v-slot="{ id, invalid }">
                    <PasswordInput :id="id" v-model="password" autocomplete="current-password" :invalid="invalid" />
                </Field>
            </form>
            <template #footer>
                <Button variant="secondary" @click="passwordOpen = false">Cancel</Button>
                <Button type="submit" form="confirm-password-form" :loading="busy" :disabled="!password">Confirm</Button>
            </template>
        </Modal>
    </SettingsSection>
</template>
