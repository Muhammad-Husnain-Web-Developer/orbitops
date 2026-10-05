<script setup>
import { Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { debounce } from '@/lib/debounce';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search…' },
    label: { type: String, default: 'Search' },
    wait: { type: Number, default: 300 },
});

const emit = defineEmits(['update:modelValue']);

const value = ref(props.modelValue ?? '');
const emitDebounced = debounce((next) => emit('update:modelValue', next), props.wait);

watch(value, (next) => emitDebounced(next));
watch(
    () => props.modelValue,
    (next) => {
        if ((next ?? '') !== value.value) value.value = next ?? '';
    },
);

function clear() {
    value.value = '';
    emitDebounced.cancel();
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="relative">
        <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-3" aria-hidden="true" />
        <input
            v-model="value"
            type="search"
            :placeholder="placeholder"
            :aria-label="label"
            class="h-9 w-full rounded-lg border border-line bg-surface pr-8 pl-9 text-body text-ink shadow-card transition-[border-color,box-shadow] placeholder:text-ink-3 hover:border-line-strong focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/20 [&::-webkit-search-cancel-button]:hidden"
            @keydown.esc="clear"
        />
        <button v-if="value" type="button" class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-ink-3 hover:text-ink" aria-label="Clear search" @click="clear">
            <X class="size-3.5" />
        </button>
    </div>
</template>
