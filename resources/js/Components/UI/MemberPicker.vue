<script setup>
import Avatar from './Avatar.vue';

const model = defineModel({ type: Array, default: () => [] });

defineProps({
    members: { type: Array, required: true },
    label: { type: String, default: 'Team' },
});

function toggle(id) {
    model.value = model.value.includes(id) ? model.value.filter((value) => value !== id) : [...model.value, id];
}
</script>

<template>
    <fieldset>
        <legend class="mb-2 text-label text-ink">{{ label }}</legend>
        <div class="flex flex-wrap gap-1.5">
            <button
                v-for="member in members"
                :key="member.id"
                type="button"
                :aria-pressed="model.includes(member.id)"
                class="inline-flex items-center gap-2 rounded-full border py-1 pr-3 pl-1 text-small transition-colors"
                :class="model.includes(member.id) ? 'border-accent/50 bg-accent/10 text-ink' : 'border-line text-ink-2 hover:border-line-strong hover:text-ink'"
                @click="toggle(member.id)"
            >
                <Avatar :user="member" size="xs" />
                {{ member.name }}
            </button>
        </div>
    </fieldset>
</template>
