<template>
  <ComponentCard
    title="Resolution health"
    desc="How fast issues are getting resolved, and how often they reopen."
  >
    <div class="flex items-baseline justify-between">
      <span class="text-sm text-gray-500 dark:text-gray-400">Median resolution</span>
      <span class="text-2xl font-semibold text-gray-800 dark:text-white/90">
        {{ medianLabel }}
      </span>
    </div>

    <div class="mt-2 flex items-baseline justify-between">
      <span class="text-sm text-gray-500 dark:text-gray-400">Sample size</span>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ resolution?.sample_size ?? 0 }} issues
      </span>
    </div>

    <div class="mt-2 flex items-baseline justify-between">
      <span class="text-sm text-gray-500 dark:text-gray-400">Reopened</span>
      <span
        class="text-sm font-medium"
        :class="reopenedCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'"
      >
        {{ reopenedCount ?? 0 }}
      </span>
    </div>

    <div
      v-if="coverageNote"
      class="mt-4 rounded-md bg-gray-50 p-3 text-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400"
    >
      {{ coverageNote }}
    </div>
  </ComponentCard>
</template>

<script setup>
import { computed } from 'vue'
import ComponentCard from '@/components/common/ComponentCard.vue'

const props = defineProps({
  resolution: {
    type: Object,
    default: () => ({ median_hours: null, sample_size: 0, legacy_sample_size: 0, coverage: 'unknown' }),
  },
  reopenedCount: { type: Number, default: 0 },
})

const medianLabel = computed(() => {
  const hours = props.resolution?.median_hours
  if (hours === null || hours === undefined) return '—'
  if (hours < 1) return `${Math.round(hours * 60)}m`
  if (hours < 24) return `${Number(hours.toFixed(1))}h`
  return `${Number((hours / 24).toFixed(1))}d`
})

const coverageNote = computed(() => {
  const coverage = props.resolution?.coverage
  const legacy = props.resolution?.legacy_sample_size ?? 0
  if (coverage === 'complete') return null
  if (coverage === 'estimated') {
    return `${legacy} issue(s) lack a resolved_at timestamp and were estimated from updated_at.`
  }
  return 'Coverage is incomplete — median is based on a partial sample and may understate real resolution time.'
})
</script>
