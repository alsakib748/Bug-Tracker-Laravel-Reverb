<template>
  <div>
    <div v-if="loading" class="space-y-5 animate-pulse">
      <div v-for="n in 5" :key="n" class="flex items-start gap-3 px-3 py-3">
        <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-800"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 w-24 rounded-md bg-gray-200 dark:bg-gray-800"></div>
          <div class="h-3 w-3/4 rounded-md bg-gray-200 dark:bg-gray-800"></div>
          <div class="h-3 w-1/3 rounded-md bg-gray-200 dark:bg-gray-800"></div>
        </div>
      </div>
    </div>

    <EmptyState
      v-else-if="logs.length === 0"
      title="No activity found"
      description="Nothing matches the current filters yet. Try adjusting your search or check back later."
    >
      <template #icon>
        <i class="pi pi-history text-3xl text-gray-400"></i>
      </template>
    </EmptyState>

    <div v-else class="space-y-6">
      <section v-for="group in groups" :key="group.key">
        <header class="flex items-center gap-3 pb-2">
          <span
            class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
          >
            {{ group.label }}
          </span>
          <span class="h-px flex-1 bg-gray-200/70 dark:bg-gray-800"></span>
          <span class="text-[11px] text-gray-400 dark:text-gray-500">
            {{ group.logs.length }} {{ group.logs.length === 1 ? 'event' : 'events' }}
          </span>
        </header>

        <div
          class="relative space-y-1 before:absolute before:bottom-3 before:left-8 before:top-3 before:w-px before:bg-gradient-to-b before:from-gray-200 before:via-gray-100 before:to-transparent before:content-[''] dark:before:from-gray-800 dark:before:via-gray-800/60 dark:before:to-transparent"
        >
          <ActivityItem v-for="log in group.logs" :key="log.id" :log="log" />
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ActivityItem from './ActivityItem.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const props = defineProps({
  logs: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})

const groups = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  const map = new Map()

  props.logs.forEach((log) => {
    const date = new Date(log.created_at)
    const key = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`
    if (!map.has(key)) {
      map.set(key, { key, label: dayLabel(date, today, yesterday), logs: [] })
    }
    map.get(key).logs.push(log)
  })

  return Array.from(map.values())
})

function dayLabel(date, today, yesterday) {
  const start = new Date(today)
  if (date.toDateString() === today.toDateString()) return 'Today'
  if (date.toDateString() === yesterday.toDateString()) return 'Yesterday'

  const diffDays = Math.floor((start - date) / 86400000)
  if (diffDays > 0 && diffDays < 7) {
    return date.toLocaleDateString('en-US', { weekday: 'long' })
  }
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() === today.getFullYear() ? undefined : 'numeric',
  })
}
</script>
