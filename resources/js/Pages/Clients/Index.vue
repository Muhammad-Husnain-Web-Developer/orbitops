<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Archive, Briefcase, Eye, MoreHorizontal, Pencil, Plus, SearchX } from '@lucide/vue';
import { computed, ref } from 'vue';
import ClientFormModal from '@/Components/Clients/ClientFormModal.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Table from '@/Components/UI/Table.vue';
import { confirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { formatMoney, formatNumber } from '@/lib/format';

const props = defineProps({
    clients: { type: Object, required: true },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const { can } = usePermissions();
const { filters, loading, reset, sortBy } = useFilters(props.filters, { route: 'clients.index', only: ['clients', 'filters'] });

const creating = ref(false);
const editing = ref(null);
const editOpen = computed({ get: () => Boolean(editing.value), set: (value) => !value && (editing.value = null) });

const hasFilters = computed(() => Boolean(filters.search || filters.status));

const columns = [
    { key: 'name', label: 'Client', sortable: true },
    { key: 'contact', label: 'Contact', hide: 'lg' },
    { key: 'status', label: 'Status' },
    { key: 'projects_count', label: 'Projects', sortable: true, align: 'right', hide: 'md' },
    { key: 'outstanding', label: 'Outstanding', sortable: true, align: 'right' },
    { key: 'lifetime_value', label: 'Lifetime value', sortable: true, align: 'right', hide: 'xl' },
    { key: 'actions', label: 'Actions', srOnly: true, align: 'right', width: 'w-12' },
];

const statuses = [
    { value: '', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'lead', label: 'Leads' },
    { value: 'inactive', label: 'Inactive' },
];

async function archive(client) {
    if (await confirm({ title: `Archive ${client.name}?`, description: 'The client is hidden from lists. Projects and invoices are kept.', confirmLabel: 'Archive client' })) {
        router.delete(route('clients.destroy', client.id), { preserveScroll: true });
    }
}
</script>

<template>
    <PageHeader title="Clients" description="Every company you work with, with projects and balances at a glance.">
        <template #actions>
            <Button v-if="can('clients.manage')" :icon="Plus" @click="creating = true">New client</Button>
        </template>
    </PageHeader>

    <dl class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="[label, value] in [['Total clients', formatNumber(stats.total)], ['Active', formatNumber(stats.active)], ['Leads', formatNumber(stats.leads)], ['Outstanding', formatMoney(stats.outstanding, 'USD', { decimals: 0 })]]" :key="label" class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card">
            <dt class="text-small text-ink-3">{{ label }}</dt>
            <dd class="mt-1 text-h2 text-ink tabular">{{ value }}</dd>
        </div>
    </dl>

    <Card :padded="false">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
            <SearchInput v-model="filters.search" placeholder="Search clients, contacts or industries…" label="Search clients" class="sm:w-80" />
            <SegmentedControl v-model="filters.status" :options="statuses" label="Filter by status" size="sm" />
        </div>

        <Table
            :columns="columns"
            :rows="clients.data"
            :sort="{ key: filters.sort, direction: filters.direction }"
            :loading="loading"
            clickable
            caption="Clients"
            @sort="sortBy"
            @row-click="(row) => router.visit(route('clients.show', row.id))"
        >
            <template #cell-name="{ row }">
                <Link :href="route('clients.show', row.id)" class="flex items-center gap-3">
                    <Avatar :name="row.name" square size="md" decorative />
                    <span class="min-w-0">
                        <span class="block truncate font-medium text-ink">{{ row.name }}</span>
                        <span class="block truncate text-caption text-ink-3">{{ row.industry ?? '—' }}</span>
                    </span>
                </Link>
            </template>
            <template #cell-contact="{ row }">
                <span class="block truncate text-ink-2">{{ row.contact_name ?? '—' }}</span>
                <span class="block truncate text-caption text-ink-3">{{ row.email }}</span>
            </template>
            <template #cell-status="{ row }"><StatusBadge group="clientStatus" :value="row.status" /></template>
            <template #cell-projects_count="{ row }">
                <span class="text-ink tabular">{{ row.active_projects_count }}</span><span class="text-ink-3 tabular"> / {{ row.projects_count }}</span>
            </template>
            <template #cell-outstanding="{ row }">
                <span class="tabular" :class="row.outstanding > 0 ? 'font-medium text-ink' : 'text-ink-3'">{{ formatMoney(row.outstanding, row.currency, { decimals: 0 }) }}</span>
            </template>
            <template #cell-lifetime_value="{ row }"><span class="text-ink-2 tabular">{{ formatMoney(row.lifetime_value, row.currency, { decimals: 0 }) }}</span></template>
            <template #cell-actions="{ row }">
                <Dropdown align="end" :label="`Actions for ${row.name}`">
                    <template #trigger="{ attrs }">
                        <button type="button" v-bind="attrs" class="rounded-md p-1.5 text-ink-3 hover:bg-hover hover:text-ink" :aria-label="`Actions for ${row.name}`"><MoreHorizontal class="size-4" /></button>
                    </template>
                    <DropdownItem :href="route('clients.show', row.id)" :icon="Eye">View client</DropdownItem>
                    <template v-if="can('clients.manage')">
                        <DropdownItem :icon="Pencil" @select="editing = row">Edit details</DropdownItem>
                    </template>
                    <template v-if="can('clients.delete')">
                        <DropdownSeparator />
                        <DropdownItem :icon="Archive" danger @select="archive(row)">Archive</DropdownItem>
                    </template>
                </Dropdown>
            </template>

            <template #mobile="{ row }">
                <Link :href="route('clients.show', row.id)" class="flex items-center gap-3">
                    <Avatar :name="row.name" square size="lg" decorative />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-body font-medium text-ink">{{ row.name }}</p>
                            <StatusBadge group="clientStatus" :value="row.status" size="sm" />
                        </div>
                        <p class="truncate text-small text-ink-3">{{ row.contact_name ?? row.industry }}</p>
                        <p class="mt-1 text-caption text-ink-3">{{ row.active_projects_count }} active projects · <span :class="row.outstanding > 0 ? 'font-medium text-ink-2' : ''">{{ formatMoney(row.outstanding, row.currency, { decimals: 0 }) }} outstanding</span></p>
                    </div>
                </Link>
            </template>

            <template #empty>
                <EmptyState v-if="hasFilters" :icon="SearchX" title="No clients match your filters" description="Try a different search term or status.">
                    <Button variant="secondary" @click="reset({ sort: filters.sort, direction: filters.direction })">Clear filters</Button>
                </EmptyState>
                <EmptyState v-else :icon="Briefcase" title="No clients yet." description="Add the companies you work with to track their projects, invoices and files in one place.">
                    <Button v-if="can('clients.manage')" :icon="Plus" @click="creating = true">Add your first client</Button>
                </EmptyState>
            </template>
        </Table>

        <Pagination v-if="clients.data.length" :meta="clients.meta" :links="clients.links" :only="['clients']" />
    </Card>

    <ClientFormModal v-model:open="creating" />
    <ClientFormModal v-model:open="editOpen" :client="editing" />
</template>
