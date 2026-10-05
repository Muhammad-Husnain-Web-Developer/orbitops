<script setup>
import { computed, ref, watch } from 'vue';
import { confirmState, settleConfirm } from '@/composables/useConfirm';
import Button from './Button.vue';
import Field from './Field.vue';
import Input from './Input.vue';
import Modal from './Modal.vue';

const typed = ref('');

const open = computed({
    get: () => confirmState.open,
    set: (value) => !value && settleConfirm(false),
});

const blocked = computed(() => Boolean(confirmState.requireText) && typed.value.trim() !== confirmState.requireText);

watch(() => confirmState.open, () => (typed.value = ''));
</script>

<template>
    <Modal v-model:open="open" :title="confirmState.title" :description="confirmState.description" size="sm" initial-focus="[data-confirm-focus]">
        <Field v-if="confirmState.requireText" :label="`Type “${confirmState.requireText}” to confirm`" v-slot="{ id }">
            <Input :id="id" v-model="typed" autocomplete="off" data-confirm-focus />
        </Field>
        <p v-else class="text-body text-ink-2">This action is logged in the workspace activity.</p>
        <template #footer>
            <Button variant="secondary" data-confirm-focus @click="settleConfirm(false)">{{ confirmState.cancelLabel }}</Button>
            <Button :variant="confirmState.tone === 'danger' ? 'danger' : 'primary'" :disabled="blocked" @click="settleConfirm(true)">
                {{ confirmState.confirmLabel }}
            </Button>
        </template>
    </Modal>
</template>
