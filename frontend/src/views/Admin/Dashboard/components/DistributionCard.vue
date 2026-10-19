<template>
  <ComponentCard :title="title" class="h-full">
    <div class="space-y-3">
      <div v-if="!rows.length" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No data for this period.
      </div>

      <div v-for="row in rows" :key="row[labelKey] ?? 'unknown'" class="space-y-1">
        <div class="flex items-center justify-between text-sm">
          <span class="font-medium capitalize text-gray-700 dark:text-gray-300">
            {{ formatLabel(row[labelKey]) }}
          </span>
          <span class="text-gray-500 dark:text-gray-400">
            {{ row.count }}
            <span class="text-gray-400 dark:text-gray-500">({{ percentOf(row.count) }}%)</span>
          </span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
          <div
            class="h-full rounded-full transition-all duration-500"
            :style="{
              width: barWidth(row.count),
              backgroundColor: colorFor(row[labelKey]),
            }"
          />
        </div>
      </div>
    </div>
  </ComponentCard>
</template>

<script setup>
import ComponentCard from '@/components/common/ComponentCard.vue'

const props = defineProps({
  title: { type: String, required: true },
  rows: { type: Array, default: () => [] },
  labelKey: { type: String, required: true },
  total: { type: Number, default: 0 },
})

const COLORS = [
  '#465fff', '#12b76a', '#f79009', '#ef4444', '#8b5cf6',
  '#0ea5e9', '#ec4899', '#14b8a6', '#f59e0b', '#64748b',
]

const colorFor = (label) => {
  const index = props.rows.findIndex((row) => row[props.labelKey] === label)
  return COLORS[(index < 0 ? 0 : index) % COLORS.length]
}

const percentOf = (count) => {
  if (!props.total) return 0
  return Math.round((count / props.total) * 100)
}

const barWidth = (count) => `${percentOf(count)}%`

const formatLabel = (value) => String(value ?? 'unknown').replace(/_/g, ' ')
</script>
