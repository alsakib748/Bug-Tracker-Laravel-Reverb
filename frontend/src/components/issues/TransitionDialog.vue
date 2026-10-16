<template>
  <Dialog :visible="modelValue" @update:visible="$emit('update:modelValue', $event)" modal
    :header="`Transition to ${statusLabel(toStatus)}`" :style="{ width: '28rem' }">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">
        Move issue from
        <strong>{{ statusLabel(fromStatus) }}</strong>
        to
        <strong>{{ statusLabel(toStatus) }}</strong>.
      </p>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Reason (optional)
        </label>
        <Textarea v-model="reason" rows="3" class="w-full"
          placeholder="e.g. Fix ready for testing" :disabled="submitting" />
      </div>

      <div v-if="errorMessage" class="text-sm text-red-600">
        {{ errorMessage }}
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" class="p-button-text" :disabled="submitting" @click="close" />
      <Button :label="submitting ? 'Submitting...' : 'Confirm'" icon="pi pi-check" :loading="submitting"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { statusLabel } from '@/services/workflowApi'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  fromStatus: {
    type: String,
    required: true,
  },
  toStatus: {
    type: String,
    default: null,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const reason = ref('')

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      reason.value = ''
    }
  }
)

const close = () => {
  if (props.submitting) return
  emit('update:modelValue', false)
}

const submit = () => {
  emit('confirm', { toStatus: props.toStatus, reason: reason.value || null })
}
</script>
