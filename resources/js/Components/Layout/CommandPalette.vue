<script setup>
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Briefcase,
    CheckSquare,
    Clock,
    CornerDownLeft,
    FileText,
    FolderKanban,
    Keyboard,
    Moon,
    Repeat,
    Search,
    Settings,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed, nextTick, ref, useId, watch } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Kbd from '@/Components/UI/Kbd.vue';
import Spinner from '@/Components/UI/Spinner.vue';
import { useDialog } from '@/composables/useDialog';
import { closePalette, palette } from '@/composables/usePalette';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';
import { useTheme } from '@/composables/useTheme';
import { api } from '@/lib/api';
import { solidSwatch, swatch } from '@/lib/colors';
import { debounce } from '@/lib/debounce';
import { primaryNav, visibleItems, workspaceNav } from '@/lib/navigation';

const emit = defineEmits(['shortcuts']);

const page = usePage();
const { can } = usePermissions();
const { toggle: toggleTheme } = useTheme();

const panel = ref(null);
const input = ref(null);
const list = ref(null);
const query = ref('');
const active = ref(0);
const remote = ref([]);
const searching = ref(false);
const failed = ref(false);
const id = useId();

const open = computed({ get: () => palette.open, set: (value) => (palette.open = value) });
useDialog(open, panel, { onClose: closePalette, initialFocus: 'input' });

const icons = { clients: Briefcase, projects: FolderKanban, tasks: CheckSquare, invoices: FileText, members: Users };

const commands = computed(() => {
    if (palette.mode === 'workspaces') {
        return [
            {
                label: 'Switch workspace',
                items: page.props.workspaces.map((workspace) => ({
                    id: `ws-${workspace.id}`,
                    title: workspace.name,
                    subtitle: workspace.id === page.props.workspace.id ? 'Current workspace' : workspace.industry,
                    swatch: workspace.accent,
                    initials: workspace.initials,
                    run: () => workspace.id !== page.props.workspace.id && router.put(route('workspaces.switch', workspace.id)),
                })),
            },
        ];
    }

    const actions = [
        { id: 'new-project', title: 'Create project', icon: FolderKanban, keywords: 'new add', permission: 'projects.manage', run: () => openQuickCreate('project') },
        { id: 'new-task', title: 'Create task', icon: CheckSquare, keywords: 'new add todo', permission: 'tasks.manage', run: () => openQuickCreate('task') },
        { id: 'new-client', title: 'Create client', icon: Briefcase, keywords: 'new add customer company', permission: 'clients.manage', run: () => openQuickCreate('client') },
        { id: 'new-invoice', title: 'Create invoice', icon: FileText, keywords: 'new bill', permission: 'invoices.manage', run: () => router.visit(route('invoices.create')) },
        { id: 'log-time', title: 'Log time', icon: Clock, keywords: 'track timer hours timesheet', permission: 'time.track', run: () => openQuickCreate('time') },
        { id: 'invite', title: 'Invite teammate', icon: UserPlus, keywords: 'member team add people', permission: 'team.manage', run: () => router.visit(route('settings.members', { invite: 1 })) },
        { id: 'switch', title: 'Switch workspace…', icon: Repeat, keywords: 'tenant company change', run: () => ((palette.mode = 'workspaces'), (query.value = ''), true) },
        { id: 'theme', title: 'Toggle dark mode', icon: Moon, keywords: 'theme light appearance', run: () => toggleTheme() },
        { id: 'shortcuts', title: 'Keyboard shortcuts', icon: Keyboard, keywords: 'help keys', run: () => emit('shortcuts') },
    ].filter((item) => !item.permission || can(item.permission));

    const navigation = [...visibleItems(primaryNav, can), ...visibleItems(workspaceNav, can)].map((item) => ({
        id: `nav-${item.route}`,
        title: `Go to ${item.label}`,
        icon: item.icon,
        keywords: item.label,
        run: () => router.visit(route(item.route)),
    }));

    const settings = [
        ['General', 'settings.general'],
        ['Workspace', 'settings.workspace'],
        ['Members', 'settings.members'],
        ['Roles & permissions', 'settings.roles'],
        ['Billing', 'settings.billing'],
        ['Notifications', 'settings.notifications'],
        ['Security', 'settings.security'],
        ['Appearance', 'settings.appearance'],
    ].map(([label, name]) => ({ id: `settings-${name}`, title: `Settings: ${label}`, icon: Settings, keywords: 'preferences', run: () => router.visit(route(name)) }));

    return [
        { label: 'Actions', items: actions },
        { label: 'Navigate', items: navigation },
        { label: 'Settings', items: settings },
    ];
});

/** Tiny fuzzy match: every query word must appear in the title or keywords. */
function matches(item, term) {
    if (!term) return true;
    const haystack = `${item.title} ${item.keywords ?? ''} ${item.subtitle ?? ''}`.toLowerCase();

    return term
        .toLowerCase()
        .split(/\s+/)
        .every((word) => haystack.includes(word));
}

const groups = computed(() => {
    const term = query.value.trim();
    const local = commands.value
        .map((group) => ({ ...group, items: group.items.filter((item) => matches(item, term)).slice(0, term ? 5 : group.label === 'Settings' ? 0 : 9) }))
        .filter((group) => group.items.length);

    const results = remote.value.map((group) => ({
        label: group.label,
        items: group.items.map((item) => ({ ...item, icon: icons[group.key], run: () => router.visit(item.url) })),
    }));

    return term ? [...results, ...local] : local;
});

const flat = computed(() => groups.value.flatMap((group) => group.items));

const search = debounce(async (term) => {
    if (term.length < 2 || palette.mode !== 'root') {
        remote.value = [];
        searching.value = false;
        return;
    }

    try {
        const response = await api.get(route('search'), { params: { q: term } });
        if (term === query.value.trim()) remote.value = response.groups;
        failed.value = false;
    } catch {
        failed.value = true;
    } finally {
        searching.value = false;
    }
}, 180);

watch(query, (value) => {
    active.value = 0;
    searching.value = value.trim().length >= 2 && palette.mode === 'root';
    search(value.trim());
});

watch(
    () => palette.open,
    (value) => {
        if (value) {
            query.value = '';
            remote.value = [];
            active.value = 0;
        } else {
            palette.mode = 'root';
        }
    },
);

watch(active, async () => {
    await nextTick();
    list.value?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' });
});

function run(item) {
    const keepOpen = item.run?.() === true;

    if (!keepOpen) {
        closePalette();
    } else {
        nextTick(() => input.value?.focus());
    }
}

function onKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = (active.value + 1) % Math.max(1, flat.value.length);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = (active.value - 1 + flat.value.length) % Math.max(1, flat.value.length);
    } else if (event.key === 'Enter' && flat.value[active.value]) {
        event.preventDefault();
        run(flat.value[active.value]);
    } else if (event.key === 'Backspace' && !query.value && palette.mode !== 'root') {
        palette.mode = 'root';
    }
}

const indexOf = (item) => flat.value.indexOf(item);
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="duration-100 ease-in" leave-to-class="opacity-0">
            <div v-if="palette.open" class="fixed inset-0 z-[65] bg-overlay backdrop-blur-[2px]" aria-hidden="true" @click="closePalette" />
        </Transition>
        <Transition enter-active-class="duration-200 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0 scale-[0.98] -translate-y-2" leave-active-class="duration-100 ease-in" leave-to-class="opacity-0 scale-[0.98]">
            <div v-if="palette.open" class="pointer-events-none fixed inset-0 z-[65] flex items-start justify-center p-3 pt-[12vh] sm:p-6 sm:pt-[14vh]">
                <div ref="panel" role="dialog" aria-modal="true" aria-label="Command palette" class="pointer-events-auto flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-line bg-elevated shadow-overlay">
                    <div class="flex items-center gap-3 border-b border-line px-4">
                        <button v-if="palette.mode !== 'root'" type="button" class="rounded-md p-1 text-ink-3 hover:bg-hover hover:text-ink" aria-label="Back" @click="palette.mode = 'root'">
                            <ArrowLeft class="size-4" />
                        </button>
                        <Search v-else class="size-[18px] shrink-0 text-ink-3" aria-hidden="true" />
                        <input
                            ref="input"
                            v-model="query"
                            type="text"
                            role="combobox"
                            :aria-expanded="true"
                            :aria-controls="`${id}-list`"
                            :aria-activedescendant="flat[active] ? `${id}-${flat[active].id}` : undefined"
                            aria-autocomplete="list"
                            :placeholder="palette.mode === 'workspaces' ? 'Find a workspace…' : 'Search or type a command…'"
                            class="h-14 flex-1 bg-transparent text-[0.9375rem] text-ink outline-none placeholder:text-ink-3"
                            autocomplete="off"
                            spellcheck="false"
                            @keydown="onKeydown"
                        />
                        <Spinner v-if="searching" class="size-4 text-ink-3" />
                        <Kbd>Esc</Kbd>
                    </div>

                    <ul :id="`${id}-list`" ref="list" role="listbox" aria-label="Results" class="flex-1 overflow-y-auto p-2">
                        <template v-for="group in groups" :key="group.label">
                            <li role="presentation" class="px-2.5 pt-2.5 pb-1 text-eyebrow text-ink-3 uppercase">{{ group.label }}</li>
                            <li
                                v-for="item in group.items"
                                :id="`${id}-${item.id}`"
                                :key="item.id"
                                role="option"
                                :aria-selected="indexOf(item) === active"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 transition-colors duration-75"
                                :class="indexOf(item) === active ? 'bg-hover' : ''"
                                @mousemove="active = indexOf(item)"
                                @click="run(item)"
                            >
                                <Avatar v-if="item.avatar" :user="item.avatar" size="sm" decorative />
                                <span v-else-if="item.swatch" class="flex size-6 items-center justify-center rounded-md text-[0.625rem] font-bold text-white" :class="solidSwatch(item.swatch)">{{ item.initials }}</span>
                                <span v-else class="flex size-6 shrink-0 items-center justify-center rounded-md border border-line bg-surface text-ink-3">
                                    <span v-if="item.color" class="size-2 rounded-full" :class="swatch(item.color)" />
                                    <component :is="item.icon" v-else class="size-3.5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-body text-ink">{{ item.title }}</span>
                                    <span v-if="item.subtitle" class="block truncate text-caption text-ink-3">{{ item.subtitle }}</span>
                                </span>
                                <CornerDownLeft v-if="indexOf(item) === active" class="size-3.5 shrink-0 text-ink-3" aria-hidden="true" />
                            </li>
                        </template>
                        <li v-if="!flat.length && !searching" class="px-4 py-12 text-center">
                            <p class="text-body font-medium text-ink">{{ failed ? 'Search is unavailable right now' : 'No results' }}</p>
                            <p class="mt-1 text-small text-ink-3">{{ failed ? 'Check your connection and try again.' : `Nothing matches “${query}”. Try a client, project, task or invoice number.` }}</p>
                        </li>
                    </ul>

                    <div class="flex items-center gap-4 border-t border-line px-4 py-2.5 text-caption text-ink-3">
                        <span class="flex items-center gap-1.5"><Kbd>↑</Kbd><Kbd>↓</Kbd>navigate</span>
                        <span class="flex items-center gap-1.5"><Kbd>↵</Kbd>open</span>
                        <span class="ml-auto hidden sm:inline">Search clients, projects, tasks, invoices &amp; people</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
