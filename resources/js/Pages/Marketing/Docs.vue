<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Seo from '@/Components/Marketing/Seo.vue';
import Kbd from '@/Components/UI/Kbd.vue';

defineProps({
    seo: { type: Object, required: true },
});

const sections = [
    { id: 'getting-started', title: 'Getting started', body: ['Create an account and name your first workspace — usually your company or team. You become its Owner.', 'From the dashboard use Quick create (or press ⌘K) to add your first client, project and tasks. Invite teammates from Team or Settings → Members.'] },
    { id: 'workspaces', title: 'Workspaces & tenancy', body: ['A workspace is a fully isolated tenant: clients, projects, tasks, time, invoices, files and roles all belong to exactly one workspace.', 'Switch workspaces from the switcher at the top of the sidebar. Each workspace has its own accent colour so you always know where you are.'] },
    { id: 'roles', title: 'Roles & permissions', body: ['Every workspace has five roles: Owner, Admin, Manager, Member and Client. Owners and Admins can tune each role in Settings → Roles & Permissions.', 'The interface hides actions you cannot take, and the server authorizes every request regardless.'] },
    { id: 'projects', title: 'Projects, milestones & tasks', body: ['Projects belong to clients and track budget, deadline, team, milestones, files and activity. Progress is calculated from completed tasks.', 'The Kanban board has Backlog, To Do, In Progress, Review and Done columns. Drag cards between columns or use the card menu to move them with the keyboard.'] },
    { id: 'time', title: 'Time tracking', body: ['Start a timer from the Time page, a task or the command palette. Only one timer can run at a time; starting another stops the current one.', 'Add manual entries for work you forgot to track, and filter timesheets by project, person and date range.'] },
    { id: 'invoicing', title: 'Invoices & expenses', body: ['Create invoices with line items, tax and percentage or fixed discounts. Sending an invoice emails the client and publishes it to their portal.', 'Sent invoices past their due date are flagged overdue automatically each morning, and your finance team is notified.'] },
    { id: 'portal', title: 'Client portal', body: ['Invite a client contact with the Client role to give them a portal. They only ever see their own projects, client-visible tasks and files, invoices and messages.', 'Milestones marked “requires approval” appear in the portal for the client to approve or request changes.'] },
    { id: 'realtime', title: 'Notifications & realtime', body: ['Notifications arrive in the bell in realtime and by email, depending on each person’s preferences in Settings → Notifications.', 'Activity and Kanban moves stream to everyone in the workspace over WebSockets using Laravel Reverb.'] },
    { id: 'api', title: 'API access', body: ['Create personal access tokens in Settings → API. Tokens are scoped to the workspace they were created in.', 'Send the token as a Bearer token to read projects, tasks and clients from /api/v1.'] },
];

const active = ref(sections[0].id);
let observer;

onMounted(() => {
    observer = new IntersectionObserver((entries) => entries.forEach((entry) => entry.isIntersecting && (active.value = entry.target.id)), { rootMargin: '-30% 0px -60% 0px' });
    sections.forEach((section) => observer.observe(document.getElementById(section.id)));
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Seo :seo="seo" />
    <div class="mx-auto max-w-7xl px-5 pt-32 pb-24 sm:px-8 sm:pt-40">
        <p class="text-eyebrow text-accent-text uppercase">Documentation</p>
        <h1 class="mt-4 text-[clamp(2.5rem,1.4rem+3.4vw,4rem)] leading-none font-semibold tracking-[-0.045em] text-ink">OrbitOps guide</h1>
        <p class="mt-5 max-w-xl text-lead text-ink-3">Everything you need to run your business in OrbitOps. Press <Kbd>⌘</Kbd> <Kbd>K</Kbd> inside the app to search and act from anywhere.</p>

        <div class="mt-14 grid gap-12 lg:grid-cols-[14rem_1fr]">
            <nav class="lg:sticky lg:top-28 lg:self-start" aria-label="On this page">
                <p class="text-eyebrow text-ink-3 uppercase">On this page</p>
                <ul class="mt-4 space-y-1 border-l border-line">
                    <li v-for="section in sections" :key="section.id">
                        <a
                            :href="`#${section.id}`"
                            class="-ml-px block border-l py-1.5 pl-4 text-small transition-colors"
                            :class="active === section.id ? 'border-accent font-medium text-ink' : 'border-transparent text-ink-3 hover:text-ink'"
                            :aria-current="active === section.id ? 'location' : undefined"
                            >{{ section.title }}</a
                        >
                    </li>
                </ul>
            </nav>
            <article class="max-w-2xl space-y-16">
                <section v-for="section in sections" :id="section.id" :key="section.id" class="scroll-mt-28">
                    <h2 class="text-h2 text-ink">{{ section.title }}</h2>
                    <p v-for="paragraph in section.body" :key="paragraph" class="mt-4 text-[0.9375rem] leading-relaxed text-ink-2">{{ paragraph }}</p>
                </section>
            </article>
        </div>
    </div>
</template>
