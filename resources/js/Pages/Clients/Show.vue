<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Archive, ArrowLeft, Clock, FileText, FolderKanban, Globe, Mail, MapPin, MoreHorizontal, Pencil, Phone, Plus, Receipt, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import ClientFormModal from '@/Components/Clients/ClientFormModal.vue';
import CommentThread from '@/Components/Comments/CommentThread.vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import Dropzone from '@/Components/Files/Dropzone.vue';
import FileList from '@/Components/Files/FileList.vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { confirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { useUrlTab } from '@/composables/useUrlTab';
import { dueLabel, formatDate, formatDuration, formatMoney } from '@/lib/format';

const props = defineProps({
    client: { type: Object, required: true },
    tab: { type: String, default: 'overview' },
    stats: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    files: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    notes: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = usePermissions();
const tab = useUrlTab(props.tab);
const editing = ref(false);

const tabs = computed(() =>
    [
        { key: 'overview', label: 'Overview' },
        { key: 'projects', label: 'Projects', count: props.projects.length },
        can('invoices.view') && { key: 'invoices', label: 'Invoices', count: props.invoices.length },
        can('files.view') && { key: 'files', label: 'Files', count: props.files.length },
        { key: 'activity', label: 'Activity' },
        { key: 'notes', label: 'Notes', count: props.notes.length },
    ].filter(Boolean),
);

const money = (value) => formatMoney(value, props.client.currency, { decimals: 0 });

const metrics = computed(() => [
    { label: 'Lifetime value', value: money(props.client.lifetime_value), icon: Wallet },
    { label: 'Outstanding', value: money(props.client.outstanding), icon: Receipt, tone: props.client.outstanding > 0 ? 'text-warning' : '' },
    { label: 'Active projects', value: props.stats.active_projects, icon: FolderKanban },
    { label: 'Time tracked', value: formatDuration(props.stats.tracked_seconds, { compact: true }), icon: Clock },
]);

function emailClient() {
    window.location.href = `mailto:${props.client.email}`;
}

async function archive() {
    if (await confirm({ title: `Archive ${props.client.name}?`, description: 'Projects, invoices and files are kept.', confirmLabel: 'Archive client' })) {
        router.delete(route('clients.destroy', props.client.id));
    }
}
</script>

<template>
    <Head :title="client.name" />

    <Link :href="route('clients.index')" class="mb-4 inline-flex items-center gap-1.5 text-small font-medium text-ink-3 hover:text-ink"><ArrowLeft class="size-3.5" />Clients</Link>

    <header class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-4">
            <Avatar :name="client.name" square size="xl" decorative />
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-h2 text-ink">{{ client.name }}</h1>
                    <StatusBadge group="clientStatus" :value="client.status" />
                </div>
                <p class="mt-1 text-body text-ink-3">{{ client.industry ?? 'Client' }} · Client since {{ formatDate(client.created_at, 'month') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Button v-if="can('projects.manage')" variant="secondary" :icon="Plus" @click="openQuickCreate('project', { client_id: client.id })">New project</Button>
            <Button v-if="can('clients.manage')" variant="secondary" :icon="Pencil" @click="editing = true">Edit</Button>
            <Dropdown align="end" label="More actions">
                <template #trigger="{ attrs }">
                    <Button v-bind="attrs" variant="secondary" square :icon="MoreHorizontal" aria-label="More actions" />
                </template>
                <DropdownItem v-if="can('invoices.manage')" :href="route('invoices.create', { client: client.id })" :icon="FileText">Create invoice</DropdownItem>
                <DropdownItem v-if="client.email" :icon="Mail" @select="emailClient">Email {{ client.contact_name ?? 'client' }}</DropdownItem>
                <template v-if="can('clients.delete')">
                    <DropdownSeparator />
                    <DropdownItem :icon="Archive" danger @select="archive">Archive client</DropdownItem>
                </template>
            </Dropdown>
        </div>
    </header>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card">
            <dt class="flex items-center gap-1.5 text-small text-ink-3"><component :is="metric.icon" class="size-3.5" aria-hidden="true" />{{ metric.label }}</dt>
            <dd class="mt-1 text-h2 tabular" :class="metric.tone || 'text-ink'">{{ metric.value }}</dd>
        </div>
    </dl>

    <Tabs v-model="tab" :tabs="tabs" label="Client sections" class="mt-8" />

    <div class="mt-6">
        <div v-if="tab === 'overview'" class="grid gap-4 lg:grid-cols-3">
            <Card title="Contact" class="lg:col-span-1">
                <dl class="space-y-3.5 text-body">
                    <div class="flex items-center gap-3"><Avatar :name="client.contact_name ?? client.name" size="md" decorative /><div><dt class="sr-only">Primary contact</dt><dd class="font-medium text-ink">{{ client.contact_name ?? 'No contact yet' }}</dd><p class="text-caption text-ink-3">Primary contact</p></div></div>
                    <div v-if="client.email" class="flex items-center gap-3 text-ink-2"><Mail class="size-4 text-ink-3" /><dt class="sr-only">Email</dt><dd><a :href="`mailto:${client.email}`" class="hover:text-ink hover:underline">{{ client.email }}</a></dd></div>
                    <div v-if="client.phone" class="flex items-center gap-3 text-ink-2"><Phone class="size-4 text-ink-3" /><dt class="sr-only">Phone</dt><dd>{{ client.phone }}</dd></div>
                    <div v-if="client.website" class="flex items-center gap-3 text-ink-2"><Globe class="size-4 text-ink-3" /><dt class="sr-only">Website</dt><dd><a :href="client.website" target="_blank" rel="noopener noreferrer" class="hover:text-ink hover:underline">{{ client.website.replace(/^https?:\/\//, '') }}</a></dd></div>
                    <div v-if="client.city || client.country" class="flex items-center gap-3 text-ink-2"><MapPin class="size-4 text-ink-3" /><dt class="sr-only">Location</dt><dd>{{ [client.city, client.country].filter(Boolean).join(', ') }}</dd></div>
                </dl>
            </Card>
            <Card title="Recent activity" class="lg:col-span-2">
                <EmptyState v-if="!activity.length" compact :icon="FolderKanban" title="No activity yet" description="Work on this client's projects shows up here." />
                <ActivityFeed v-else :items="activity.slice(0, 6)" />
            </Card>
            <section class="lg:col-span-3">
                <h2 class="mb-3 text-h3 text-ink">Projects</h2>
                <div v-if="projects.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <ProjectCard v-for="project in projects.slice(0, 3)" :key="project.id" :project="project" />
                </div>
                <Card v-else><EmptyState compact :icon="FolderKanban" title="No projects yet." description="Create the first project for this client." /></Card>
            </section>
        </div>

        <div v-else-if="tab === 'projects'">
            <div v-if="projects.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <ProjectCard v-for="project in projects" :key="project.id" :project="project" />
            </div>
            <Card v-else>
                <EmptyState :icon="FolderKanban" title="No projects yet." :description="`Create the first project for ${client.name} and bring your team into the workflow.`">
                    <Button v-if="can('projects.manage')" :icon="Plus" @click="openQuickCreate('project', { client_id: client.id })">Create Project</Button>
                </EmptyState>
            </Card>
        </div>

        <Card v-else-if="tab === 'invoices'" :padded="false">
            <EmptyState v-if="!invoices.length" :icon="FileText" title="No invoices yet." description="Bill this client for tracked time and fixed-price work.">
                <Button v-if="can('invoices.manage')" :href="route('invoices.create', { client: client.id })" :icon="Plus">Create Invoice</Button>
            </EmptyState>
            <ul v-else class="divide-y divide-line">
                <li v-for="invoice in invoices" :key="invoice.id">
                    <Link :href="route('invoices.show', invoice.id)" class="flex items-center gap-4 px-5 py-3.5 hover:bg-hover">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-subtle text-ink-3"><FileText class="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium text-ink">{{ invoice.number }}</span>
                            <span class="block text-caption text-ink-3">Issued {{ formatDate(invoice.issue_date) }} · {{ invoice.status === 'paid' ? `Paid ${formatDate(invoice.paid_at)}` : dueLabel(invoice.due_date) }}</span>
                        </span>
                        <StatusBadge group="invoiceStatus" :value="invoice.status" />
                        <span class="w-28 text-right font-medium text-ink tabular">{{ formatMoney(invoice.total, invoice.currency) }}</span>
                    </Link>
                </li>
            </ul>
        </Card>

        <Card v-else-if="tab === 'files'" title="Files" description="Contracts, briefs and deliverables for this client.">
            <Dropzone v-if="can('files.manage')" :data="{ client_id: client.id }" :only="['files']" compact class="mb-4" />
            <FileList :files="files" show-project :only="['files']" />
        </Card>

        <Card v-else-if="tab === 'activity'" title="Activity">
            <EmptyState v-if="!activity.length" compact :icon="FolderKanban" title="No activity yet" description="Actions on this client's projects and invoices appear here." />
            <ActivityFeed v-else :items="activity" grouped />
        </Card>

        <Card v-else-if="tab === 'notes'" title="Internal notes" description="Only your team can see these.">
            <CommentThread
                :comments="notes"
                :action="can('clients.manage') ? route('clients.notes.store', client.id) : null"
                :only="['notes']"
                placeholder="Add a note about this client…"
                empty-title="No notes yet"
                empty-description="Capture preferences, decisions and context your team should know."
                submit-label="Add note"
            />
        </Card>
    </div>

    <ClientFormModal v-model:open="editing" :client="client" />
</template>
