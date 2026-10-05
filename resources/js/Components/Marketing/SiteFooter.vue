<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import Logo from '@/Components/UI/Logo.vue';
import ThemeToggle from '@/Components/UI/ThemeToggle.vue';

const page = usePage();

const columns = [
    { title: 'Product', links: [['Features', 'features'], ['Pricing', 'pricing'], ['Security', 'features', 'security'], ['Roadmap', 'changelog', 'roadmap']] },
    { title: 'Company', links: [['About', 'about'], ['Contact', 'contact']] },
    { title: 'Resources', links: [['Documentation', 'docs'], ['Changelog', 'changelog']] },
    { title: 'Legal', links: [['Privacy', 'privacy'], ['Terms', 'terms']] },
];

const href = ([, name, hash]) => route(name) + (hash ? `#${hash}` : '');

const socials = [
    { key: 'github', label: 'OrbitOps on GitHub', path: 'M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.37-3.88-1.37-.53-1.33-1.28-1.69-1.28-1.69-1.05-.71.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.71 1.26 3.37.96.1-.75.4-1.26.73-1.55-2.56-.29-5.25-1.28-5.25-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a11 11 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.7 5.39-5.27 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5Z' },
    { key: 'linkedin', label: 'OrbitOps on LinkedIn', path: 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z' },
    { key: 'x', label: 'OrbitOps on X', path: 'M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.21-6.82-5.97 6.82H1.67l7.73-8.84L1.25 2.25h6.83l4.71 6.23 5.45-6.23Zm-1.16 17.52h1.83L7.08 4.13H5.12l11.96 15.64Z' },
];
</script>

<template>
    <footer class="relative overflow-hidden border-t border-line bg-surface/40">
        <div class="mx-auto max-w-7xl px-5 pt-16 pb-10 sm:px-8">
            <div class="grid gap-12 lg:grid-cols-[1.4fr_2fr]">
                <div class="max-w-xs">
                    <Logo />
                    <p class="mt-4 text-body text-ink-3">Run your business. Not your spreadsheets. One workspace for clients, projects, time and revenue.</p>
                    <div class="mt-6 flex items-center gap-1">
                        <a
                            v-for="social in socials"
                            :key="social.key"
                            :href="page.props.social?.[social.key] ?? '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="social.label"
                            class="inline-flex size-9 items-center justify-center rounded-lg text-ink-3 transition-colors hover:bg-hover hover:text-ink"
                        >
                            <svg viewBox="0 0 24 24" class="size-4 fill-current" aria-hidden="true"><path :d="social.path" /></svg>
                        </a>
                        <ThemeToggle />
                    </div>
                </div>
                <nav class="grid grid-cols-2 gap-8 sm:grid-cols-4" aria-label="Footer">
                    <div v-for="column in columns" :key="column.title">
                        <h2 class="text-eyebrow text-ink-3 uppercase">{{ column.title }}</h2>
                        <ul class="mt-4 space-y-2.5">
                            <li v-for="link in column.links" :key="link[0]">
                                <Link :href="href(link)" class="text-body text-ink-2 transition-colors hover:text-ink">{{ link[0] }}</Link>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <div class="mt-14 flex flex-col gap-3 border-t border-line pt-6 text-small text-ink-3 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ new Date().getFullYear() }} OrbitOps. All rights reserved.</p>
                <p>Built with Laravel, Inertia.js, Vue, Tailwind CSS &amp; GSAP.</p>
            </div>
        </div>
        <p class="pointer-events-none -mb-[0.22em] text-center text-[clamp(4rem,17vw,15rem)] leading-none font-semibold tracking-[-0.06em] text-ink/[0.035] select-none" aria-hidden="true">OrbitOps</p>
    </footer>
</template>
