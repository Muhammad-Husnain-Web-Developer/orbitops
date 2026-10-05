<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Square } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatDuration, toDate } from '@/lib/format';
import { swatch } from '@/lib/colors';

defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const timer = computed(() => page.props.timer);
const now = ref(Date.now());
const stopping = ref(false);
let interval;

onMounted(() => (interval = setInterval(() => (now.value = Date.now()), 1000)));
onBeforeUnmount(() => clearInterval(interval));

const elapsed = computed(() => (timer.value ? Math.max(0, Math.floor((now.value - toDate(timer.value.started_at).getTime()) / 1000)) : 0));

function stop() {
    router.post(route('timer.stop'), {}, {
        preserveScroll: true,
        onStart: () => (stopping.value = true),
        onFinish: () => (stopping.value = false),
    });
}
</script>

<template>
    <Transition enter-active-class="duration-300 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0 translate-y-2" leave-active-class="duration-200" leave-to-class="opacity-0">
        <div v-if="timer" class="rounded-xl border border-line bg-elevated p-2.5 shadow-card" :class="collapsed ? 'flex justify-center' : ''" role="timer" :aria-label="`Timer running: ${formatDuration(elapsed)}`">
            <button v-if="collapsed" type="button" class="relative flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger" :title="`Stop timer (${formatDuration(elapsed, { clock: true })})`" @click="stop">
                <Square class="size-3.5 fill-current" />
                <span class="absolute -top-0.5 -right-0.5 size-2 animate-pulse rounded-full bg-danger" />
            </button>
            <div v-else class="flex items-center gap-2.5">
                <span class="size-2 shrink-0 animate-pulse rounded-full bg-danger" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-body font-medium text-ink tabular">{{ formatDuration(elapsed, { clock: true }) }}</p>
                    <p class="flex items-center gap-1.5 truncate text-caption text-ink-3">
                        <span class="size-1.5 shrink-0 rounded-full" :class="swatch(timer.project?.color)" />
                        <span class="truncate">{{ timer.task?.title ?? timer.project?.name }}</span>
                    </p>
                </div>
                <button type="button" class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-danger/10 text-danger transition-colors hover:bg-danger/20 disabled:opacity-50" :disabled="stopping" aria-label="Stop timer" @click="stop">
                    <Square class="size-3 fill-current" />
                </button>
            </div>
        </div>
    </Transition>
</template>
