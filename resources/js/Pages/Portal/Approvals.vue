<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardCheck, MessageSquareWarning, ThumbsUp } from '@lucide/vue';
import { ref } from 'vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Field from '@/Components/UI/Field.vue';
import Modal from '@/Components/UI/Modal.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { swatch } from '@/lib/colors';
import { formatDate, formatRelative } from '@/lib/format';

defineProps({
    pending: { type: Array, required: true },
    history: { type: Array, required: true },
});

const reviewing = ref(null);
const form = useForm({ decision: 'approved', note: '' });

function open(milestone, decision) {
    form.reset();
    form.clearErrors();
    form.decision = decision;
    reviewing.value = milestone;
}

function submit() {
    form.post(route('portal.approvals.review', reviewing.value.id), {
        preserveScroll: true,
        onSuccess: () => (reviewing.value = null),
    });
}
</script>

<template>
    <Head title="Approvals" />

    <header class="mb-6">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Approvals</h1>
        <p class="mt-1 text-body text-ink-3">Sign off delivered milestones, or tell the team what should change.</p>
    </header>

    <section class="mb-8" aria-labelledby="pending-heading">
        <h2 id="pending-heading" class="mb-3 text-label text-ink-3">Waiting for you</h2>
        <Card v-if="!pending.length">
            <EmptyState :icon="CheckCircle2" title="Nothing to review" description="When the team delivers a milestone that needs your sign-off, it will appear here." compact />
        </Card>
        <div v-else class="space-y-4">
            <article v-for="milestone in pending" :key="milestone.id" class="rounded-xl border border-accent/30 bg-surface p-5 shadow-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <Link v-if="milestone.project" :href="route('portal.projects.show', milestone.project.id)" class="flex items-center gap-1.5 text-small text-ink-3 hover:text-ink"><span class="size-2 rounded-full" :class="swatch(milestone.project.color)" />{{ milestone.project.name }}</Link>
                        <h3 class="mt-1 text-h3 text-ink">{{ milestone.name }}</h3>
                        <p v-if="milestone.description" class="mt-1 text-small text-ink-2">{{ milestone.description }}</p>
                        <p class="mt-2 text-caption text-ink-3">Delivered {{ milestone.completed_at ? formatRelative(milestone.completed_at) : 'recently' }}<template v-if="milestone.due_date"> · planned for {{ formatDate(milestone.due_date) }}</template></p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <Button variant="secondary" :icon="MessageSquareWarning" @click="open(milestone, 'changes_requested')">Request changes</Button>
                        <Button :icon="ThumbsUp" @click="open(milestone, 'approved')">Approve</Button>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section v-if="history.length" aria-labelledby="history-heading">
        <h2 id="history-heading" class="mb-3 text-label text-ink-3">Previously reviewed</h2>
        <Card :padded="false">
            <ul class="divide-y divide-line">
                <li v-for="milestone in history" :key="milestone.id" class="flex items-start gap-3 px-5 py-3.5">
                    <component :is="milestone.approval_status === 'approved' ? CheckCircle2 : MessageSquareWarning" class="mt-0.5 size-5 shrink-0" :class="milestone.approval_status === 'approved' ? 'text-success' : 'text-warning'" />
                    <div class="min-w-0 flex-1">
                        <p class="text-body text-ink">{{ milestone.name }} <span class="text-ink-3">· {{ milestone.project?.name }}</span></p>
                        <p v-if="milestone.approval_note" class="mt-0.5 text-small text-ink-2">“{{ milestone.approval_note }}”</p>
                    </div>
                    <Badge :tone="milestone.approval_status === 'approved' ? 'success' : 'warning'" size="sm">{{ milestone.approval_status === 'approved' ? `Approved ${formatDate(milestone.approved_at, 'short')}` : 'Changes requested' }}</Badge>
                </li>
            </ul>
        </Card>
    </section>

    <Modal
        :open="Boolean(reviewing)"
        :title="form.decision === 'approved' ? `Approve “${reviewing?.name}”?` : `Request changes to “${reviewing?.name}”`"
        :description="form.decision === 'approved' ? 'The team is notified straight away and moves on to the next step.' : 'Explain what should change. The milestone goes back to the team.'"
        @update:open="(value) => !value && (reviewing = null)"
    >
        <form id="review-form" novalidate @submit.prevent="submit">
            <Field :label="form.decision === 'approved' ? 'Note for the team' : 'What should change?'" :hint="form.decision === 'approved' ? 'Optional' : null" :error="form.errors.note" :required="form.decision !== 'approved'" v-slot="{ id, invalid }">
                <Textarea :id="id" v-model="form.note" :rows="4" autosize :placeholder="form.decision === 'approved' ? 'Looks great, thank you!' : 'The hero copy should lead with the new positioning…'" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <Button variant="secondary" @click="reviewing = null">Cancel</Button>
            <Button type="submit" form="review-form" :icon="form.decision === 'approved' ? ThumbsUp : ClipboardCheck" :loading="form.processing">{{ form.decision === 'approved' ? 'Approve milestone' : 'Send to the team' }}</Button>
        </template>
    </Modal>
</template>
