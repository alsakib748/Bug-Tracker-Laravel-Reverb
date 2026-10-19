<template>
  <div class="space-y-2">
    <div class="flex items-center gap-3">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Status:</span>
      <AppBadge size="sm" :color="statusBadgeColor(currentStatus)">
        {{ statusLabel(currentStatus) }}
      </AppBadge>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <Select
        v-model="selectedStatus"
        :options="options"
        optionLabel="label"
        optionValue="value"
        placeholder="Change status"
        :disabled="transitioning || options.length === 0"
        class="flex-1"
      />

      <AppButton
        size="sm"
        :loading="transitioning"
        :disabled="!selectedStatus"
        :onClick="requestTransition"
      >
        Update Status
      </AppButton>

      <span v-if="options.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
        No further transitions available.
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Select from 'primevue/select'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import { statusLabel, STATUS_LABELS } from '@/services/workflowApi'
import { statusBadgeColor } from '@/utils/issueMeta'

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
  })),
)

const requestTransition = () => {
  if (!selectedStatus.value) return
  emit('transition', selectedStatus.value)
  selectedStatus.value = null
}
</script>
