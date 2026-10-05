<script setup>
import { Menu, Search } from '@lucide/vue';
import Kbd from '@/Components/UI/Kbd.vue';
import { modKey } from '@/composables/useHotkeys';
import { openPalette } from '@/composables/usePalette';
import NotificationCenter from './NotificationCenter.vue';
import QuickCreateMenu from './QuickCreateMenu.vue';
import UserMenu from './UserMenu.vue';

defineEmits(['open-menu', 'shortcuts']);
</script>

<template>
    <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-canvas/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
        <button type="button" class="-ml-1 inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-hover hover:text-ink lg:hidden" aria-label="Open navigation" @click="$emit('open-menu')">
            <Menu class="size-5" />
        </button>

        <button
            type="button"
            class="group hidden h-9 w-full max-w-sm items-center gap-2.5 rounded-lg border border-line bg-surface px-3 text-body text-ink-3 shadow-card transition-colors hover:border-line-strong hover:text-ink-2 sm:flex"
            aria-label="Search or run a command"
            @click="openPalette()"
        >
            <Search class="size-4" />
            <span class="flex-1 text-left">Search or jump to…</span>
            <span class="flex gap-0.5"><Kbd>{{ modKey }}</Kbd><Kbd>K</Kbd></span>
        </button>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <button type="button" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-hover hover:text-ink sm:hidden" aria-label="Search" @click="openPalette()">
                <Search class="size-[18px]" />
            </button>
            <div class="hidden sm:block">
                <QuickCreateMenu />
            </div>
            <NotificationCenter />
            <UserMenu @shortcuts="$emit('shortcuts')" />
        </div>
    </header>
</template>
