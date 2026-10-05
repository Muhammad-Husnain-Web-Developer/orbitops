<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, FileText, Folder, FolderKanban, LayoutDashboard, ListChecks, MessageSquare } from '@lucide/vue';
import { computed, onBeforeUnmount, watch } from 'vue';
import UserMenu from '@/Components/Layout/UserMenu.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import Logo from '@/Components/UI/Logo.vue';
import Toaster from '@/Components/UI/Toaster.vue';

const page = usePage();
const portal = computed(() => page.props.portal ?? {});
const workspace = computed(() => page.props.workspace ?? {});

const nav = computed(() => [
    { label: 'Overview', route: 'portal.dashboard', match: 'portal.dashboard', icon: LayoutDashboard },
    { label: 'Projects', route: 'portal.projects.index', match: 'portal.projects.*', icon: FolderKanban },
    { label: 'Tasks', route: 'portal.tasks', match: 'portal.tasks', icon: ListChecks },
    { label: 'Files', route: 'portal.files', match: 'portal.files', icon: Folder },
    { label: 'Invoices', route: 'portal.invoices.index', match: 'portal.invoices.*', icon: FileText, count: portal.value.unpaid },
    { label: 'Messages', route: 'portal.messages', match: 'portal.messages', icon: MessageSquare },
    { label: 'Approvals', route: 'portal.approvals', match: 'portal.approvals', icon: CheckCircle2, count: portal.value.approvals, urgent: true },
]);

// The agency's accent colours the portal, so it feels like their own client space.
watch(() => workspace.value.accent, (accent) => (document.documentElement.dataset.accent = accent ?? 'violet'), { immediate: true });
onBeforeUnmount(() => delete document.documentElement.dataset.accent);
</script>

<template>
    <div class="min-h-dvh bg-canvas print:bg-white">
        <a href="#main" class="sr-only z-[80] rounded-md bg-accent px-3 py-2 text-accent-ink focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

        <header class="sticky top-0 z-30 border-b border-line bg-canvas/85 backdrop-blur-xl print:hidden">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6">
                <Link :href="route('portal.dashboard')" class="flex min-w-0 items-center gap-3">
                    <Avatar :user="workspace" :name="workspace.name" square size="md" decorative />
                    <span class="min-w-0">
                        <span class="block truncate text-body font-semibold text-ink">{{ workspace.name }}</span>
                        <span class="block truncate text-caption text-ink-3">Client portal<template v-if="portal.client"> · {{ portal.client.name }}</template></span>
                    </span>
                </Link>
                <div class="ml-auto flex items-center gap-2">
                    <UserMenu portal />
                </div>
            </div>
            <nav class="mx-auto max-w-6xl px-2 sm:px-4" aria-label="Portal">
                <ul class="no-scrollbar -mb-px flex gap-1 overflow-x-auto">
                    <li v-for="item in nav" :key="item.route">
                        <Link
                            :href="route(item.route)"
                            class="relative inline-flex h-11 items-center gap-2 border-b-2 px-3 text-body font-medium whitespace-nowrap transition-colors"
                            :class="route().current(item.match) ? 'border-accent text-ink' : 'border-transparent text-ink-3 hover:text-ink'"
                            :aria-current="route().current(item.match) ? 'page' : undefined"
                        >
                            <component :is="item.icon" class="size-4" aria-hidden="true" />
                            {{ item.label }}
                            <span v-if="item.count" class="rounded-full px-1.5 text-caption tabular" :class="item.urgent ? 'bg-accent text-accent-ink' : 'bg-subtle text-ink-2'">{{ item.count }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>
        </header>

        <main id="main" class="mx-auto w-full max-w-6xl px-4 pt-6 pb-16 sm:px-6 sm:pt-8 print:max-w-none print:p-0" tabindex="-1">
            <div :key="page.component" class="animate-page-in">
                <slot />
            </div>
        </main>

        <footer class="border-t border-line py-6 print:hidden">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 text-caption text-ink-3 sm:flex-row sm:px-6">
                <p>Questions? Message the {{ workspace.name }} team from any project.</p>
                <p class="flex items-center gap-1.5">Powered by <Logo :size="14" class="opacity-80" /></p>
            </div>
        </footer>

        <ConfirmDialog />
        <Toaster />
    </div>
</template>
