<script setup>
import { useForm } from '@inertiajs/vue3';
import { MessageSquare, Send } from '@lucide/vue';
import { nextTick, onMounted, ref, watch } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    messages: { type: Array, required: true },
    projectId: { type: Number, required: true },
    only: { type: Array, default: () => [] },
    teamName: { type: String, default: 'the team' },
});

const form = useForm({ body: '' });
const list = ref(null);

function scrollToEnd() {
    nextTick(() => list.value?.lastElementChild?.scrollIntoView({ block: 'nearest' }));
}

onMounted(scrollToEnd);
watch(() => props.messages.length, scrollToEnd);

function send() {
    if (!form.body.trim()) return;

    form.post(route('portal.messages.store', props.projectId), {
        preserveScroll: true,
        only: props.only.length ? props.only : undefined,
        onSuccess: () => form.reset(),
    });
}

function onKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') send();
}
</script>

<template>
    <div>
        <EmptyState v-if="!messages.length" :icon="MessageSquare" title="Start the conversation" :description="`Ask a question or share feedback. ${teamName} will reply here.`" compact />
        <ol v-else ref="list" class="space-y-5" aria-label="Messages">
            <li v-for="message in messages" :key="message.id" class="flex gap-3" :class="message.is_mine ? 'flex-row-reverse' : ''">
                <Avatar :user="message.author" size="md" decorative class="mt-0.5" />
                <div class="max-w-[80%] min-w-0" :class="message.is_mine ? 'text-right' : ''">
                    <p class="mb-1 text-caption text-ink-3">
                        <span class="font-medium text-ink-2">{{ message.is_mine ? 'You' : message.author?.name ?? 'Former member' }}</span>
                        <template v-if="!message.is_mine && !message.from_client && message.author?.title"> · {{ message.author.title }}</template>
                        · <time :datetime="message.created_at">{{ formatRelative(message.created_at) }}</time>
                    </p>
                    <div class="inline-block rounded-2xl px-4 py-2.5 text-left text-body whitespace-pre-line" :class="message.is_mine ? 'rounded-tr-md bg-accent text-accent-ink' : 'rounded-tl-md border border-line bg-surface text-ink'">{{ message.body }}</div>
                </div>
            </li>
        </ol>

        <form class="mt-6 rounded-xl border border-line bg-surface p-2 shadow-card focus-within:border-accent" @submit.prevent="send">
            <label :for="`message-${projectId}`" class="sr-only">Write a message</label>
            <Textarea :id="`message-${projectId}`" v-model="form.body" :rows="2" autosize placeholder="Write a message…" class="!border-0 !bg-transparent !shadow-none focus:!ring-0" :invalid="Boolean(form.errors.body)" @keydown="onKeydown" />
            <div class="flex items-center justify-between gap-3 px-1 pt-1">
                <p class="text-caption text-ink-3" :class="form.errors.body ? 'text-danger' : ''">{{ form.errors.body ?? 'Ctrl + Enter to send' }}</p>
                <Button type="submit" size="sm" :icon="Send" :loading="form.processing" :disabled="!form.body.trim()">Send</Button>
            </div>
        </form>
    </div>
</template>
