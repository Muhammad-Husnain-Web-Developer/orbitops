<script setup>
import { History, Layers, Radio, ShieldCheck } from '@lucide/vue';

const pillars = [
    { icon: Layers, title: 'Tenant isolation', body: 'Every record belongs to a workspace and every query is scoped to it automatically — and checked again by policies.' },
    { icon: ShieldCheck, title: 'Role-based access', body: 'Owner, Admin, Manager, Member and Client roles per workspace, each with an editable permission matrix.' },
    { icon: Radio, title: 'Realtime by default', body: 'Notifications, activity and board moves stream to your team over WebSockets the moment they happen.' },
    { icon: History, title: 'Audit-ready history', body: 'Important actions land in an activity log your team and auditors can actually read.' },
];
</script>

<template>
    <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.1fr] lg:gap-16">
        <div>
            <p data-reveal class="mb-4 inline-flex items-center gap-2 text-eyebrow text-accent-text uppercase"><span class="h-px w-6 bg-accent/60" />Engineered for trust</p>
            <h2 data-reveal class="text-h1 text-balance text-ink">Multi-tenant by design. Not as an afterthought.</h2>
            <p data-reveal class="mt-5 text-lead text-ink-3">Your clients' data never mixes with anyone else's. OrbitOps is built on boring, proven infrastructure with security in every layer.</p>
            <dl class="mt-10 grid gap-6 sm:grid-cols-2">
                <div v-for="pillar in pillars" :key="pillar.title" data-reveal>
                    <dt class="flex items-center gap-2.5 text-body font-semibold text-ink"><component :is="pillar.icon" class="size-4 text-accent-text" aria-hidden="true" />{{ pillar.title }}</dt>
                    <dd class="mt-1.5 text-body text-ink-3">{{ pillar.body }}</dd>
                </div>
            </dl>
        </div>

        <div data-reveal class="overflow-hidden rounded-2xl border border-line bg-[#0c0c12] shadow-overlay" aria-hidden="true">
            <div class="flex items-center gap-1.5 border-b border-white/8 px-4 py-3">
                <span class="size-2.5 rounded-full bg-white/15" />
                <span class="size-2.5 rounded-full bg-white/15" />
                <span class="size-2.5 rounded-full bg-white/15" />
                <span class="ml-3 font-mono text-[0.6875rem] text-white/40">app/Models/Scopes/WorkspaceScope.php</span>
            </div>
            <pre class="overflow-x-auto p-5 font-mono text-[0.8125rem] leading-relaxed text-white/80"><code><span class="text-white/35">// Applied to every tenant-owned model.</span>
<span class="text-[#c4b5fd]">public function</span> <span class="text-[#67e8f9]">apply</span>(Builder $builder, Model $model): <span class="text-[#c4b5fd]">void</span>
{
    $workspaceId = app(CurrentWorkspace::<span class="text-[#c4b5fd]">class</span>)-><span class="text-[#67e8f9]">id</span>();

    <span class="text-[#c4b5fd]">if</span> ($workspaceId !== <span class="text-[#fca5a5]">null</span>) {
        $builder-><span class="text-[#67e8f9]">where</span>(
            $model-><span class="text-[#67e8f9]">qualifyColumn</span>(<span class="text-[#86efac]">'workspace_id'</span>),
            $workspaceId,
        );
    }
}

<span class="text-white/35">// Project::withProgress()->open()->get();</span>
<span class="text-white/35">// → select * from projects where workspace_id = 12 …</span></code></pre>
        </div>
    </div>
</template>
