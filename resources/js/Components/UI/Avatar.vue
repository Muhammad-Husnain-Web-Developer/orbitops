<script setup>
import { computed } from 'vue';
import { avatarTint } from '@/lib/colors';
import { initials as toInitials } from '@/lib/format';

const props = defineProps({
    user: { type: Object, default: null },
    name: { type: String, default: null },
    src: { type: String, default: null },
    size: { type: String, default: 'md' },
    square: { type: Boolean, default: false },
    // Set when the name is already visible next to the avatar, so it isn't announced twice.
    decorative: { type: Boolean, default: false },
});

const sizes = {
    xs: 'size-5 text-[0.5625rem]',
    sm: 'size-6 text-[0.625rem]',
    md: 'size-8 text-caption',
    lg: 'size-10 text-small',
    xl: 'size-14 text-[1.0625rem]',
};

const label = computed(() => props.user?.name ?? props.name ?? '');
const image = computed(() => props.src ?? props.user?.avatar_url ?? props.user?.logo_url ?? null);
const letters = computed(() => props.user?.initials ?? toInitials(label.value));
</script>

<template>
    <span
        class="relative inline-flex shrink-0 items-center justify-center overflow-hidden font-semibold tracking-tight select-none"
        :class="[sizes[size] ?? sizes.md, square ? 'rounded-[28%]' : 'rounded-full', image ? 'bg-subtle' : avatarTint(label)]"
        :title="label || undefined"
        :aria-hidden="decorative || undefined"
    >
        <img v-if="image" :src="image" :alt="decorative ? '' : label" class="size-full object-cover" loading="lazy" />
        <span v-else aria-hidden="true">{{ letters }}</span>
        <span v-if="!image && !decorative" class="sr-only">{{ label }}</span>
    </span>
</template>
