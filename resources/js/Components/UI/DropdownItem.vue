<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, inject } from 'vue';

const props = defineProps({
    href: { type: String, default: null },
    method: { type: String, default: null },
    icon: { type: [Object, Function], default: null },
    shortcut: { type: String, default: null },
    danger: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    active: { type: Boolean, default: false },
    keepOpen: { type: Boolean, default: false },
    external: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);
const dropdown = inject('dropdown', null);

// External links (files, receipts) are plain anchors in a new tab, not Inertia visits.
const component = computed(() => (props.href && props.external ? 'a' : props.href ? Link : 'button'));

function select(event) {
    if (props.disabled) {
        event.preventDefault();
        return;
    }

    emit('select', event);

    if (!props.keepOpen) {
        dropdown?.close(!props.href);
    }
}
</script>

<template>
    <component
        :is="component"
        :href="href ?? undefined"
        :method="method ?? undefined"
        :as="href && method && method !== 'get' ? 'button' : undefined"
        :type="href ? undefined : 'button'"
        :target="external ? '_blank' : undefined"
        :rel="external ? 'noopener' : undefined"
        role="menuitem"
        tabindex="-1"
        :aria-disabled="disabled || undefined"
        class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-body outline-none transition-colors duration-100 focus:bg-hover aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
        :class="[danger ? 'text-danger focus:bg-danger/10 hover:bg-danger/10' : 'text-ink hover:bg-hover', active ? 'bg-hover' : '']"
        @click="select"
    >
        <component :is="icon" v-if="icon" class="size-4 shrink-0" :class="danger ? '' : 'text-ink-3'" aria-hidden="true" />
        <span class="min-w-0 flex-1 truncate"><slot /></span>
        <span v-if="shortcut" class="text-caption text-ink-3">{{ shortcut }}</span>
        <slot name="end" />
    </component>
</template>
