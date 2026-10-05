<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, null], default: '' });

const props = defineProps({
    invalid: { type: Boolean, default: false },
    autosize: { type: Boolean, default: false },
    rows: { type: Number, default: 4 },
});

const el = ref(null);

function resize() {
    if (!props.autosize || !el.value) return;
    el.value.style.height = 'auto';
    el.value.style.height = `${el.value.scrollHeight + 2}px`;
}

onMounted(resize);
watch(model, () => nextTick(resize));

defineExpose({ focus: () => el.value?.focus() });
</script>

<template>
    <textarea
        ref="el"
        v-model="model"
        v-bind="$attrs"
        :rows="rows"
        :aria-invalid="invalid || undefined"
        class="block w-full resize-y rounded-lg border border-line bg-surface px-3 py-2 text-body text-ink shadow-card transition-[border-color,box-shadow] duration-150 placeholder:text-ink-3 hover:border-line-strong focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/20 disabled:opacity-60 aria-invalid:border-danger aria-invalid:focus:ring-danger/20"
        :class="autosize ? 'resize-none overflow-hidden' : ''"
    />
</template>
