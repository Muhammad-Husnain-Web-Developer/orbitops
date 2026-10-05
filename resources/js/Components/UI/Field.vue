<script setup>
import { CircleAlert } from '@lucide/vue';
import { computed, useId } from 'vue';

const props = defineProps({
    label: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    id: { type: String, default: null },
});

const generated = useId();
const fieldId = computed(() => props.id ?? `field-${generated}`);
const describedby = computed(() => (props.error ? `${fieldId.value}-error` : props.hint ? `${fieldId.value}-hint` : undefined));
</script>

<template>
    <div class="space-y-1.5">
        <div v-if="label || $slots.aside" class="flex items-baseline justify-between gap-3">
            <label v-if="label" :for="fieldId" class="text-label text-ink">
                {{ label }}
                <span v-if="required" class="text-danger" aria-hidden="true">*</span>
            </label>
            <span v-if="optional" class="text-caption text-ink-3">Optional</span>
            <slot name="aside" />
        </div>
        <slot :id="fieldId" :invalid="Boolean(error)" :describedby="describedby" />
        <p v-if="error" :id="`${fieldId}-error`" class="flex items-start gap-1.5 text-small text-danger" role="alert">
            <CircleAlert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
            {{ error }}
        </p>
        <p v-else-if="hint" :id="`${fieldId}-hint`" class="text-small text-ink-3">{{ hint }}</p>
    </div>
</template>
