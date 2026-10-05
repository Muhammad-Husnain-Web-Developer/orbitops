<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CommandPalette from '@/Components/Layout/CommandPalette.vue';
import MobileNav from '@/Components/Layout/MobileNav.vue';
import QuickCreateHost from '@/Components/Layout/QuickCreateHost.vue';
import ShortcutsDialog from '@/Components/Layout/ShortcutsDialog.vue';
import SidebarNav from '@/Components/Layout/SidebarNav.vue';
import TimerWidget from '@/Components/Layout/TimerWidget.vue';
import Topbar from '@/Components/Layout/Topbar.vue';
import WorkspaceSwitcher from '@/Components/Layout/WorkspaceSwitcher.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import Drawer from '@/Components/UI/Drawer.vue';
import Logo from '@/Components/UI/Logo.vue';
import Toaster from '@/Components/UI/Toaster.vue';
import { useHotkeys } from '@/composables/useHotkeys';
import { openPalette } from '@/composables/usePalette';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';

const page = usePage();
const { can } = usePermissions();

const STORAGE_KEY = 'orbitops:sidebar-collapsed';
const collapsed = ref(false);
const mobileMenu = ref(false);
const shortcuts = ref(false);

try {
    collapsed.value = localStorage.getItem(STORAGE_KEY) === '1';
} catch {
    // Storage unavailable: default to expanded.
}

function toggleSidebar() {
    collapsed.value = !collapsed.value;

    try {
        localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // Ignore.
    }
}

// The active workspace recolours the product (on <html> so teleported overlays match).
watch(
    () => page.props.workspace?.accent,
    (accent) => {
        document.documentElement.dataset.accent = accent ?? 'violet';
    },
    { immediate: true },
);

let removeNavigateListener;
onMounted(() => (removeNavigateListener = router.on('navigate', () => (mobileMenu.value = false))));
onBeforeUnmount(() => {
    removeNavigateListener?.();
    delete document.documentElement.dataset.accent;
});

useHotkeys({
    'mod+k': () => openPalette(),
    '/': () => openPalette(),
    'shift+?': () => (shortcuts.value = true),
    c: () => can('clients.manage') && openQuickCreate('client'),
    p: () => can('projects.manage') && openQuickCreate('project'),
    t: () => can('tasks.manage') && openQuickCreate('task'),
    l: () => can('time.track') && openQuickCreate('time'),
    i: () => can('invoices.manage') && router.visit(route('invoices.create')),
});
</script>

<template>
    <div class="min-h-dvh bg-canvas print:bg-white">
        <a href="#main" class="sr-only z-[80] rounded-md bg-accent px-3 py-2 text-accent-ink focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

        <!-- Desktop sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-line bg-surface/60 px-3 pt-3 pb-3 backdrop-blur-xl transition-[width] duration-300 ease-[var(--ease-out-expo)] lg:flex print:!hidden"
            :class="collapsed ? 'w-[68px]' : 'w-[248px]'"
            aria-label="Sidebar"
        >
            <WorkspaceSwitcher :collapsed="collapsed" />
            <div class="mt-5 flex min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden">
                <SidebarNav :collapsed="collapsed" />
            </div>
            <div class="mt-3 space-y-2">
                <TimerWidget :collapsed="collapsed" />
                <button
                    type="button"
                    class="flex h-8 w-full items-center gap-2.5 rounded-lg px-2.5 text-small text-ink-3 transition-colors hover:bg-hover hover:text-ink"
                    :class="collapsed ? 'justify-center px-0' : ''"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-expanded="!collapsed"
                    @click="toggleSidebar"
                >
                    <PanelLeftOpen v-if="collapsed" class="size-4" />
                    <template v-else><PanelLeftClose class="size-4" />Collapse</template>
                </button>
            </div>
        </aside>

        <!-- Mobile navigation drawer -->
        <Drawer v-model:open="mobileMenu" side="left" width="max-w-[300px]">
            <template #header>
                <Logo />
            </template>
            <div class="flex h-full flex-col gap-5 p-3">
                <WorkspaceSwitcher />
                <SidebarNav />
                <TimerWidget />
            </div>
        </Drawer>

        <div class="transition-[padding] duration-300 ease-[var(--ease-out-expo)]" :class="collapsed ? 'lg:pl-[68px] print:pl-0' : 'lg:pl-[248px] print:pl-0'">
            <Topbar class="print:!hidden" @open-menu="mobileMenu = true" @shortcuts="shortcuts = true" />
            <main id="main" class="mx-auto w-full max-w-[1440px] px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:pt-8 lg:pb-12 print:max-w-none print:p-0" tabindex="-1">
                <div :key="page.component" class="animate-page-in">
                    <slot />
                </div>
            </main>
        </div>

        <MobileNav class="print:!hidden" />
        <CommandPalette @shortcuts="shortcuts = true" />
        <QuickCreateHost />
        <ShortcutsDialog v-model:open="shortcuts" />
        <ConfirmDialog />
        <Toaster />
    </div>
</template>
