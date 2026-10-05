<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Spinner from './Spinner.vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    href: { type: String, default: null },
    external: { type: Boolean, default: false },
    method: { type: String, default: null },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    square: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
    icon: { type: [Object, Function], default: null },
    iconRight: { type: [Object, Function], default: null },
});

const variants = {
    primary: 'bg-accent text-accent-ink shadow-[inset_0_1px_0_rgb(255_255_255/0.2),0_1px_2px_rgb(0_0_0/0.18)] hover:bg-accent-hover',
    secondary: 'border border-line bg-surface text-ink shadow-card hover:border-line-strong hover:bg-hover',
    ghost: 'text-ink-2 hover:bg-hover hover:text-ink',
    soft: 'bg-accent/10 text-accent-text hover:bg-accent/16',
    danger: 'bg-danger-solid text-white shadow-[inset_0_1px_0_rgb(255_255_255/0.15)] hover:brightness-110',
    'danger-ghost': 'text-danger hover:bg-danger/10',
    link: 'text-accent-text underline-offset-4 hover:underline',
};

const sizes = {
    xs: 'h-7 gap-1.5 rounded-md px-2.5 text-caption',
    sm: 'h-8 gap-1.5 rounded-md px-3 text-small',
    md: 'h-9 gap-2 rounded-lg px-3.5 text-body',
    lg: 'h-11 gap-2 rounded-lg px-5 text-[0.9375rem]',
};

const squareSizes = { xs: 'w-7 px-0', sm: 'w-8 px-0', md: 'w-9 px-0', lg: 'w-11 px-0' };
const iconSizes = { xs: 'size-3.5', sm: 'size-4', md: 'size-4', lg: 'size-[18px]' };

const isDisabled = computed(() => props.disabled || props.loading);

const classes = computed(() => [
    'relative inline-flex shrink-0 select-none items-center justify-center whitespace-nowrap font-medium transition-[background-color,border-color,color,box-shadow,transform,filter] duration-150 ease-out active:translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
    variants[props.variant] ?? variants.primary,
    props.variant === 'link' ? 'h-auto px-0' : sizes[props.size],
    props.square && props.variant !== 'link' ? squareSizes[props.size] : '',
    props.block ? 'w-full' : '',
    isDisabled.value ? 'pointer-events-none opacity-55' : 'cursor-pointer',
]);

const component = computed(() => {
    if (!props.href) return 'button';

    return props.external ? 'a' : Link;
});

const attributes = computed(() => {
    if (!props.href) {
        return { type: props.type, disabled: isDisabled.value, 'aria-busy': props.loading || undefined };
    }

    if (props.external) {
        return { href: props.href, target: '_blank', rel: 'noopener noreferrer' };
    }

    return { href: props.href, method: props.method ?? undefined, as: props.method && props.method !== 'get' ? 'button' : undefined };
});
</script>

<template>
    <component :is="component" v-bind="attributes" :class="classes">
        <span class="inline-flex items-center justify-center gap-[inherit]" :class="loading ? 'opacity-0' : ''">
            <component :is="icon" v-if="icon" :class="iconSizes[size]" aria-hidden="true" />
            <slot />
            <component :is="iconRight" v-if="iconRight" :class="iconSizes[size]" aria-hidden="true" />
        </span>
        <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
            <Spinner :class="iconSizes[size]" />
            <span class="sr-only">Loading</span>
        </span>
    </component>
</template>
