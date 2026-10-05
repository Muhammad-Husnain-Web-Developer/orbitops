<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Menu, X } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Logo from '@/Components/UI/Logo.vue';
import ThemeToggle from '@/Components/UI/ThemeToggle.vue';
import { useDialog } from '@/composables/useDialog';

const page = usePage();
const scrolled = ref(false);
const menuOpen = ref(false);
const menuPanel = ref(null);

const links = [
    { label: 'Features', route: 'features' },
    { label: 'Pricing', route: 'pricing' },
    { label: 'About', route: 'about' },
    { label: 'Docs', route: 'docs' },
    { label: 'Contact', route: 'contact' },
];

const onScroll = () => (scrolled.value = window.scrollY > 8);
let removeNavigateListener;

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    removeNavigateListener = router.on('navigate', () => (menuOpen.value = false));
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    removeNavigateListener?.();
});

useDialog(menuOpen, menuPanel, { onClose: () => (menuOpen.value = false) });
</script>

<template>
    <header
        data-site-header
        class="fixed inset-x-0 top-0 z-40 transition-[background-color,border-color,backdrop-filter] duration-300"
        :class="scrolled ? 'border-b border-line bg-canvas/75 backdrop-blur-xl' : 'border-b border-transparent'"
    >
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-5 sm:px-8" aria-label="Main">
            <Link :href="route('home')" class="rounded-lg" aria-label="OrbitOps home">
                <Logo />
            </Link>

            <ul class="hidden items-center gap-1 md:flex">
                <li v-for="link in links" :key="link.route">
                    <Link
                        :href="route(link.route)"
                        class="rounded-md px-3 py-2 text-body font-medium transition-colors"
                        :class="route().current(link.route) ? 'text-ink' : 'text-ink-3 hover:text-ink'"
                        :aria-current="route().current(link.route) ? 'page' : undefined"
                        >{{ link.label }}</Link
                    >
                </li>
            </ul>

            <div class="flex items-center gap-1.5">
                <!-- Display is owned by this wrapper: components already set their own display utility. -->
                <div class="hidden items-center gap-1.5 sm:flex">
                    <ThemeToggle />
                    <Button v-if="page.props.auth.user" :href="route('dashboard')" size="sm" :icon-right="ArrowRight">Open dashboard</Button>
                    <template v-else>
                        <Button :href="route('login')" variant="ghost" size="sm">Log in</Button>
                        <Button :href="route('register')" size="sm">Start Building</Button>
                    </template>
                </div>
                <button
                    type="button"
                    class="inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-hover hover:text-ink md:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Open menu"
                    @click="menuOpen = true"
                >
                    <Menu class="size-5" />
                </button>
            </div>
        </nav>
    </header>

    <Teleport to="body">
        <Transition enter-active-class="duration-300 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0" leave-active-class="duration-200" leave-to-class="opacity-0">
            <div v-if="menuOpen" id="mobile-menu" ref="menuPanel" role="dialog" aria-modal="true" aria-label="Menu" class="fixed inset-0 z-50 flex flex-col bg-canvas md:hidden" tabindex="-1">
                <div class="flex h-16 items-center justify-between px-5">
                    <Logo />
                    <button type="button" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-hover" aria-label="Close menu" @click="menuOpen = false">
                        <X class="size-5" />
                    </button>
                </div>
                <ul class="flex flex-1 flex-col gap-1 px-5 pt-6">
                    <li v-for="(link, index) in links" :key="link.route" class="animate-pop-in" :style="{ animationDelay: `${index * 40}ms` }">
                        <Link :href="route(link.route)" class="block rounded-lg py-3 text-[1.75rem] font-semibold tracking-[-0.03em] text-ink">{{ link.label }}</Link>
                    </li>
                </ul>
                <div class="safe-bottom space-y-2 border-t border-line p-5">
                    <div class="flex items-center justify-between pb-2">
                        <span class="text-small text-ink-3">Appearance</span>
                        <ThemeToggle />
                    </div>
                    <template v-if="page.props.auth.user">
                        <Button :href="route('dashboard')" size="lg" block>Open dashboard</Button>
                    </template>
                    <template v-else>
                        <Button :href="route('register')" size="lg" block>Start Building</Button>
                        <Button :href="route('login')" variant="secondary" size="lg" block>Log in</Button>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
