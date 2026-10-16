<template>
  <div class="space-y-2">
    <div class="flex items-center gap-3">
      <span class="text-sm font-medium text-gray-700">Status:</span>
      <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700">
        {{ statusLabel(currentStatus) }}
      </span>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <Select v-model="selectedStatus" :options="options" optionLabel="label" optionValue="value"
        placeholder="Change status" :disabled="transitioning || options.length === 0" />

      <Button @click="requestTransition" :loading="transitioning" :disabled="!selectedStatus"
        label="Update Status" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" />

      <span v-if="options.length === 0" class="text-sm text-gray-500">
        No further transitions available.
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { statusLabel, STATUS_LABELS } from '@/services/workflowApi'

const props = defineProps({
  currentStatus: {
    type: String,
    required: true,
  },
  allowedTransitions: {
    type: Array,
    default: () => [],
  },
  transitioning: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['transition'])

const selectedStatus = ref(null)

const options = computed(() =>
  props.allowedTransitions.map((status) => ({
    label: STATUS_LABELS[status] || status,
    value: status,
  }))
)

const requestTransition = () => {
  if (!selectedStatus.value) return
  emit('transition', selectedStatus.value)
  selectedStatus.value = null
}
</script>
