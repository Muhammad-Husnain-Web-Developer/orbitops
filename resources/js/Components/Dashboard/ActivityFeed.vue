<script setup>
import { computed } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { activityIcon } from '@/lib/activity';
import { formatRelative } from '@/lib/format';
import { groupByDay } from '@/lib/notifications';

const props = defineProps({
    items: { type: Array, required: true },
    grouped: { type: Boolean, default: false },
});

const groups = computed(() => (props.grouped ? groupByDay(props.items) : [{ label: null, items: props.items }]));
</script>

<template>
    <div class="space-y-6">
        <section v-for="group in groups" :key="group.label ?? 'all'">
            <h3 v-if="group.label" class="mb-3 text-eyebrow text-ink-3 uppercase">{{ group.label }}</h3>
            <TransitionGroup tag="ol" class="relative" enter-active-class="duration-500 ease-[var(--ease-out-expo)]" enter-from-class="opacity-0 -translate-y-2">
                <li v-for="(item, index) in group.items" :key="item.id" class="relative flex gap-3 pb-5 last:pb-0">
                    <span v-if="index < group.items.length - 1" class="absolute top-9 bottom-1 left-4 w-px bg-line" aria-hidden="true" />
                    <span class="relative shrink-0">
                        <Avatar :user="item.causer" :name="item.causer ? null : 'System'" size="md" decorative />
                        <span class="absolute -right-1 -bottom-1 flex size-4.5 items-center justify-center rounded-full ring-2 ring-surface" :class="activityIcon(item.event).tone">
                            <component :is="activityIcon(item.event).icon" class="size-2.5" aria-hidden="true" />
                        </span>
                    </span>
                    <div class="min-w-0 pt-1">
                        <p class="text-body text-ink-2">
                            <span class="font-medium text-ink">{{ item.causer?.name ?? 'OrbitOps' }}</span>
                            {{ item.description }}
                            <span v-if="item.label" class="font-medium text-ink">{{ item.label }}</span>
                        </p>
                        <p class="mt-0.5 text-caption text-ink-3"><time :datetime="item.created_at">{{ formatRelative(item.created_at) }}</time></p>
                    </div>
                </li>
            </TransitionGroup>
        </section>
    </div>
</template>
