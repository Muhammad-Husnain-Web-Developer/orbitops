<script setup>
import SiteFooter from '@/Components/Marketing/SiteFooter.vue';
import SiteHeader from '@/Components/Marketing/SiteHeader.vue';
import Toaster from '@/Components/UI/Toaster.vue';
</script>

<template>
    <div class="relative min-h-dvh overflow-x-clip bg-canvas text-ink">
        <a href="#main" class="sr-only z-50 rounded-md bg-accent px-3 py-2 text-accent-ink focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>
        <SiteHeader />
        <main id="main">
            <slot />
        </main>
        <SiteFooter />
        <Toaster />
    </div>
</template>
