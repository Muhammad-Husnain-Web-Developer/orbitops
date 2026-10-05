<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { Briefcase, Clock, Copy, MailPlus, MoreHorizontal, Pencil, RefreshCw, Trash2, UserMinus, UserPlus, Users, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InviteModal from '@/Components/Team/InviteModal.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Select from '@/Components/UI/Select.vue';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    members: { type: Array, required: true },
    clients: { type: Array, required: true },
    invitations: { type: Array, default: () => [] },
    seats: { type: Object, required: true },
    roles: { type: Array, required: true },
    clientOptions: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const inviting = ref(false);
const roleLabel = (value) => ({ owner: 'Owner', admin: 'Admin', manager: 'Manager', member: 'Member', client: 'Client' })[value] ?? value;
const roleTone = (value) => ({ owner: 'accent', admin: 'info', manager: 'success', member: 'neutral', client: 'warning' })[value] ?? 'neutral';
const seatPercent = computed(() => (props.seats.limit ? (props.seats.used / props.seats.limit) * 100 : 0));
const editable = (member) => props.canManage && !member.is_owner && !member.is_me;

const options = { preserveScroll: true, only: ['members', 'clients', 'invitations', 'seats'] };

function changeRole(member, role) {
    router.patch(route('team.members.update', member.id), { role }, options);
}

async function remove(member, portal = false) {
    const ok = await confirm({
        title: portal ? `Remove ${member.name}'s portal access?` : `Remove ${member.name} from the workspace?`,
        description: portal
            ? 'They will no longer be able to see projects, files or invoices in the client portal.'
            : `They lose access immediately. ${member.open_tasks ? `Their ${member.open_tasks} open ${member.open_tasks === 1 ? 'task becomes' : 'tasks become'} unassigned.` : ''} Time they tracked is kept.`,
        confirmLabel: portal ? 'Remove access' : 'Remove member',
    });

    if (ok) router.delete(route('team.members.destroy', member.id), options);
}

// Edit details
const editing = ref(null);
const details = useForm({ title: '', weekly_capacity: 40 });
const editOpen = computed({ get: () => Boolean(editing.value), set: (value) => !value && (editing.value = null) });

function edit(member) {
    details.defaults({ title: member.title ?? '', weekly_capacity: member.weekly_capacity });
    details.reset();
    details.clearErrors();
    editing.value = member;
}

function saveDetails() {
    details.patch(route('team.members.update', editing.value.id), { ...options, onSuccess: () => (editing.value = null) });
}

// Invitations
async function copyLink(invitation) {
    await navigator.clipboard?.writeText(invitation.url);
    toast.success('Invite link copied', { description: `Only ${invitation.email} can use it.` });
}

function resend(invitation) {
    router.post(route('team.invitations.resend', invitation.id), {}, options);
}

async function revoke(invitation) {
    if (await confirm({ title: `Revoke the invitation for ${invitation.email}?`, description: 'The link stops working. You can invite them again later.', confirmLabel: 'Revoke' })) {
        router.delete(route('team.invitations.destroy', invitation.id), options);
    }
}

const utilizationTone = (value) => (value > 100 ? 'warning' : value >= 70 ? 'success' : 'accent');
</script>

<template>
    <PageHeader title="Team" description="Who's in the workspace, what they can do and how busy they are.">
        <template #actions>
            <Button v-if="canManage" :icon="UserPlus" @click="inviting = true">Invite people</Button>
        </template>
    </PageHeader>

    <div class="mb-6 flex flex-col gap-3 rounded-xl border border-line bg-surface p-4 shadow-card sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-lg bg-accent/10 text-accent-text"><Users class="size-4" /></span>
            <div>
                <p class="text-body font-medium text-ink">{{ members.length }} team {{ members.length === 1 ? 'member' : 'members' }}<span class="text-ink-3"> · {{ clients.length }} client portal {{ clients.length === 1 ? 'user' : 'users' }}</span></p>
                <p class="text-small text-ink-3">{{ seats.plan }} plan · {{ seats.limit === null ? 'Unlimited seats' : `${seats.used} of ${seats.limit} seats used${invitations.length ? ', including pending invites' : ''}` }}</p>
            </div>
        </div>
        <ProgressBar v-if="seats.limit" :value="seatPercent" :tone="seatPercent >= 90 ? 'warning' : 'accent'" size="sm" class="sm:w-56" label="Seats used" />
    </div>

    <!-- Members -->
    <Card :padded="false" class="mb-6">
        <template #header>
            <div>
                <h2 class="text-h3 text-ink">Members</h2>
                <p class="mt-0.5 text-small text-ink-3">Utilization covers the last 4 weeks against each person's weekly capacity.</p>
            </div>
        </template>
        <ul class="divide-y divide-line border-t border-line">
            <li v-for="member in members" :key="member.id" class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-3 px-4 py-3.5 sm:px-5 lg:grid-cols-[minmax(0,1.5fr)_140px_minmax(0,1fr)_150px_40px]">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="relative shrink-0">
                        <Avatar :user="member" size="lg" decorative />
                        <span v-if="member.tracking" class="absolute -right-0.5 -bottom-0.5 size-3 rounded-full border-2 border-surface bg-danger" title="Tracking time now" />
                    </span>
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 truncate text-body font-medium text-ink">
                            {{ member.name }}
                            <Badge v-if="member.is_me" size="sm">You</Badge>
                        </p>
                        <p class="truncate text-small text-ink-3">{{ member.title || member.email }}</p>
                    </div>
                </div>

                <div class="justify-self-end lg:justify-self-start">
                    <Select
                        v-if="editable(member)"
                        :model-value="member.role"
                        :options="roles"
                        size="sm"
                        :aria-label="`Role for ${member.name}`"
                        class="w-32"
                        @update:model-value="changeRole(member, $event)"
                    />
                    <Badge v-else :tone="roleTone(member.role)">{{ roleLabel(member.role) }}</Badge>
                </div>

                <div class="col-span-2 lg:col-span-1">
                    <div class="mb-1 flex justify-between text-caption text-ink-3 tabular">
                        <span>{{ member.hours }}h · {{ member.open_tasks }} open {{ member.open_tasks === 1 ? 'task' : 'tasks' }}</span>
                        <span class="font-medium" :class="member.utilization > 100 ? 'text-warning' : 'text-ink-2'">{{ member.utilization }}%</span>
                    </div>
                    <ProgressBar :value="Math.min(member.utilization, 100)" :tone="utilizationTone(member.utilization)" size="sm" :label="`${member.name} utilization`" />
                </div>

                <p class="hidden truncate text-small text-ink-3 lg:block">
                    <span v-if="member.tracking" class="inline-flex items-center gap-1.5 text-danger"><Clock class="size-3.5" />Tracking now</span>
                    <template v-else-if="member.last_active_at">Active {{ formatRelative(member.last_active_at) }}</template>
                    <template v-else>Not active yet</template>
                </p>

                <div class="hidden justify-self-end lg:block">
                    <Dropdown v-if="editable(member)" align="end" width="w-52" :label="`Actions for ${member.name}`">
                        <template #trigger="{ attrs }"><Button v-bind="attrs" variant="ghost" size="sm" square :icon="MoreHorizontal" :aria-label="`Actions for ${member.name}`" /></template>
                        <DropdownItem :icon="Pencil" @select="edit(member)">Edit title & capacity</DropdownItem>
                        <DropdownSeparator />
                        <DropdownItem :icon="UserMinus" danger @select="remove(member)">Remove from workspace</DropdownItem>
                    </Dropdown>
                </div>

                <div v-if="editable(member)" class="col-span-2 flex gap-2 lg:hidden">
                    <Button variant="secondary" size="sm" :icon="Pencil" class="flex-1" @click="edit(member)">Edit</Button>
                    <Button variant="danger-ghost" size="sm" :icon="UserMinus" class="flex-1" @click="remove(member)">Remove</Button>
                </div>
            </li>
        </ul>
    </Card>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Pending invitations -->
        <Card v-if="canManage" title="Pending invitations" :description="invitations.length ? `${invitations.length} waiting to be accepted` : null">
            <ul v-if="invitations.length" class="divide-y divide-line">
                <li v-for="invitation in invitations" :key="invitation.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-dashed border-line-strong text-ink-3"><MailPlus class="size-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-body text-ink">{{ invitation.email }}</p>
                        <p class="truncate text-caption text-ink-3">
                            {{ roleLabel(invitation.role) }}<template v-if="invitation.client"> · {{ invitation.client }}</template> · invited by {{ invitation.invited_by ?? 'a teammate' }} · expires {{ formatRelative(invitation.expires_at) }}
                        </p>
                    </div>
                    <Dropdown align="end" width="w-44" :label="`Actions for invitation to ${invitation.email}`">
                        <template #trigger="{ attrs }"><Button v-bind="attrs" variant="ghost" size="sm" square :icon="MoreHorizontal" :aria-label="`Actions for invitation to ${invitation.email}`" /></template>
                        <DropdownItem :icon="Copy" @select="copyLink(invitation)">Copy invite link</DropdownItem>
                        <DropdownItem :icon="RefreshCw" @select="resend(invitation)">Resend email</DropdownItem>
                        <DropdownSeparator />
                        <DropdownItem :icon="X" danger @select="revoke(invitation)">Revoke</DropdownItem>
                    </Dropdown>
                </li>
            </ul>
            <EmptyState v-else :icon="MailPlus" title="No pending invitations" description="Invite teammates or give a client access to their portal." compact>
                <Button variant="secondary" :icon="UserPlus" @click="inviting = true">Invite people</Button>
            </EmptyState>
        </Card>

        <!-- Client portal users -->
        <Card title="Client portal access" description="People from your clients who can see their projects and invoices" :class="canManage ? '' : 'lg:col-span-2'">
            <ul v-if="clients.length" class="divide-y divide-line">
                <li v-for="person in clients" :key="person.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <Avatar :user="person" size="md" decorative />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-body text-ink">{{ person.name }}</p>
                        <p class="flex items-center gap-1.5 truncate text-caption text-ink-3"><Briefcase class="size-3" />{{ person.client?.name }} · {{ person.last_active_at ? `active ${formatRelative(person.last_active_at)}` : 'not signed in yet' }}</p>
                    </div>
                    <Button v-if="canManage" variant="ghost" size="sm" :icon="Trash2" :aria-label="`Remove portal access for ${person.name}`" @click="remove(person, true)" />
                </li>
            </ul>
            <EmptyState v-else :icon="Briefcase" title="No client users yet" description="Invite a client contact with the Client role to share a portal." compact />
        </Card>
    </div>

    <InviteModal v-model:open="inviting" :roles="roles" :clients="clientOptions" :seats="seats" />

    <Modal v-model:open="editOpen" :title="editing ? `Edit ${editing.name}` : ''" description="Capacity is used for utilization and planning." size="sm">
        <form id="member-form" class="space-y-4" novalidate @submit.prevent="saveDetails">
            <Field label="Job title" :error="details.errors.title" v-slot="{ id, invalid }">
                <Input :id="id" v-model="details.title" placeholder="e.g. Senior Designer" :invalid="invalid" />
            </Field>
            <Field label="Weekly capacity" :error="details.errors.weekly_capacity" hint="Hours per week available for project work." v-slot="{ id, invalid }">
                <Input :id="id" v-model="details.weekly_capacity" type="number" min="0" max="80" suffix="hours" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <Button variant="secondary" @click="editing = null">Cancel</Button>
            <Button type="submit" form="member-form" :loading="details.processing">Save</Button>
        </template>
    </Modal>
</template>
