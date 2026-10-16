<template>
  <div class="mt-6">
    <h2 class="text-xl text-gray-700 mb-3">Status History:</h2>

    <div v-if="loading" class="flex justify-center py-4">
      <i class="pi pi-spin pi-spinner text-blue-600" style="font-size: 1.5rem"></i>
    </div>

    <div v-else-if="history.length === 0" class="text-gray-500 py-2">
      No status changes recorded yet.
    </div>

    <ul v-else class="space-y-3">
      <li v-for="entry in history" :key="entry.id"
        class="border-l-4 border-blue-500 bg-gray-50 rounded px-3 py-2">
        <div class="text-sm text-gray-800">
          <span class="font-medium">{{ statusLabel(entry.from_status) }}</span>
          <i class="pi pi-arrow-right mx-2 text-gray-400 text-xs"></i>
          <span class="font-medium">{{ statusLabel(entry.to_status) }}</span>
        </div>
        <div class="text-xs text-gray-500 mt-1">
          By {{ entry.actor?.name || 'System' }} &middot; {{ formatDate(entry.created_at) }}
        </div>
        <div v-if="entry.reason" class="text-xs text-gray-600 italic mt-1">
          "{{ entry.reason }}"
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { statusLabel } from '@/services/workflowApi'

defineProps({
  history: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>
