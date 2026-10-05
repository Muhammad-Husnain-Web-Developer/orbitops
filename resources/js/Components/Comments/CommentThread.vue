<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { MessageSquare, Send, Trash2 } from '@lucide/vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { confirm } from '@/composables/useConfirm';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    comments: { type: Array, required: true },
    action: { type: String, default: null },
    placeholder: { type: String, default: 'Write a comment… Use @name to mention a teammate' },
    emptyTitle: { type: String, default: 'No comments yet' },
    emptyDescription: { type: String, default: 'Start the conversation — mention teammates with @name.' },
    variant: { type: String, default: 'thread' },
    submitLabel: { type: String, default: 'Comment' },
    only: { type: Array, default: () => [] },
});

const page = usePage();
const form = useForm({ body: '' });

function submit() {
    if (!form.body.trim() || !props.action) return;

    form.post(props.action, {
        preserveScroll: true,
        only: props.only.length ? props.only : undefined,
        onSuccess: () => form.reset(),
    });
}

async function remove(comment) {
    if (await confirm({ title: 'Delete this comment?', description: 'This cannot be undone.', confirmLabel: 'Delete' })) {
        router.delete(route('comments.destroy', comment.id), { preserveScroll: true, only: props.only.length ? props.only : undefined });
    }
}

/** Split text so @mentions can be highlighted without using v-html. */
function parts(body) {
    return body.split(/(@[\p{L}][\p{L}-]*)/u).map((text) => ({ text, mention: text.startsWith('@') }));
}
</script>

<template>
    <div>
        <EmptyState v-if="!comments.length" compact :icon="MessageSquare" :title="emptyTitle" :description="emptyDescription" />

        <ol v-else class="space-y-5" :class="variant === 'chat' ? 'space-y-3' : ''">
            <li
                v-for="comment in comments"
                :key="comment.id"
                class="group flex gap-3"
                :class="variant === 'chat' && comment.is_mine ? 'flex-row-reverse' : ''"
            >
                <Avatar :user="comment.author" size="md" class="shrink-0" decorative />
                <div class="min-w-0" :class="variant === 'chat' ? 'max-w-[85%]' : 'flex-1'">
                    <div class="flex items-baseline gap-2" :class="variant === 'chat' && comment.is_mine ? 'flex-row-reverse' : ''">
                        <span class="text-body font-medium text-ink">{{ comment.author?.name ?? 'Former member' }}</span>
                        <span v-if="comment.from_client" class="rounded-full bg-warning/12 px-1.5 text-[0.6875rem] font-medium text-warning">Client</span>
                        <time class="text-caption text-ink-3" :datetime="comment.created_at">{{ formatRelative(comment.created_at) }}</time>
                        <button
                            v-if="comment.is_mine && variant !== 'chat'"
                            type="button"
                            class="ml-auto rounded p-1 text-ink-3 opacity-0 transition-opacity group-hover:opacity-100 hover:text-danger focus-visible:opacity-100"
                            aria-label="Delete comment"
                            @click="remove(comment)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                    <p
                        class="mt-1 text-body leading-relaxed whitespace-pre-line text-ink-2"
                        :class="variant === 'chat' ? (comment.is_mine ? 'rounded-2xl rounded-tr-md bg-accent px-3.5 py-2 text-accent-ink' : 'rounded-2xl rounded-tl-md bg-subtle px-3.5 py-2') : ''"
                    >
                        <template v-for="(part, index) in parts(comment.body)" :key="index">
                            <span v-if="part.mention" class="rounded bg-accent/10 px-0.5 font-medium text-accent-text" :class="variant === 'chat' && comment.is_mine ? 'bg-white/20 text-accent-ink' : ''">{{ part.text }}</span>
                            <template v-else>{{ part.text }}</template>
                        </template>
                    </p>
                </div>
            </li>
        </ol>

        <form v-if="action" class="mt-5 flex gap-3" @submit.prevent="submit">
            <Avatar :user="page.props.auth.user" size="md" class="hidden shrink-0 sm:inline-flex" />
            <div class="flex-1">
                <Textarea
                    v-model="form.body"
                    :rows="2"
                    autosize
                    :placeholder="placeholder"
                    :invalid="Boolean(form.errors.body)"
                    aria-label="Comment"
                    @keydown.meta.enter.prevent="submit"
                    @keydown.ctrl.enter.prevent="submit"
                />
                <p v-if="form.errors.body" class="mt-1.5 text-small text-danger" role="alert">{{ form.errors.body }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-caption text-ink-3">⌘ / Ctrl + Enter to send</span>
                    <Button type="submit" size="sm" :icon="Send" :loading="form.processing" :disabled="!form.body.trim()">{{ submitLabel }}</Button>
                </div>
            </div>
        </form>
    </div>
</template>
