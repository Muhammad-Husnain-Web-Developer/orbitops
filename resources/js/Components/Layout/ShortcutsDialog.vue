<script setup>
import Kbd from '@/Components/UI/Kbd.vue';
import Modal from '@/Components/UI/Modal.vue';
import { modKey } from '@/composables/useHotkeys';

const open = defineModel('open', { type: Boolean, default: false });

const groups = [
    { title: 'General', items: [[[modKey, 'K'], 'Open command palette'], [['/'], 'Search'], [['?'], 'Show keyboard shortcuts']] },
    { title: 'Create', items: [[['C'], 'New client'], [['P'], 'New project'], [['T'], 'New task'], [['I'], 'New invoice'], [['L'], 'Log time']] },
    { title: 'In lists & menus', items: [[['↑', '↓'], 'Move selection'], [['↵'], 'Open'], [['Esc'], 'Close']] },
];
</script>

<template>
    <Modal v-model:open="open" title="Keyboard shortcuts" size="md">
        <div class="grid gap-6 sm:grid-cols-2">
            <section v-for="group in groups" :key="group.title">
                <h3 class="mb-2 text-eyebrow text-ink-3 uppercase">{{ group.title }}</h3>
                <dl class="space-y-2">
                    <div v-for="[keys, label] in group.items" :key="label" class="flex items-center justify-between gap-4">
                        <dt class="text-body text-ink-2">{{ label }}</dt>
                        <dd class="flex gap-1"><Kbd v-for="key in keys" :key="key">{{ key }}</Kbd></dd>
                    </div>
                </dl>
            </section>
        </div>
    </Modal>
</template>
