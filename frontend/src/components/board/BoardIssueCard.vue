<template>
  <div
    class="cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md active:cursor-grabbing dark:border-gray-700 dark:bg-gray-800"
    @click="$emit('click', issue)"
  >
    <div class="flex items-center justify-between gap-2">
      <span class="font-mono text-xs text-gray-400">{{ issue.issue_key }}</span>
      <span
        v-if="issue.story_points"
        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700"
      >
        {{ issue.story_points }} SP
      </span>
    </div>

    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
      {{ issue.title }}
    </p>

    <div class="mt-2 flex items-center justify-between gap-2">
      <span
        class="rounded-full px-2 py-0.5 text-xs font-semibold"
        :class="priorityClass"
      >
        {{ issue.priority_label }}
      </span>
      <span
        v-if="issue.assignee"
        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-xs font-semibold text-white"
        :title="issue.assignee.name"
      >
        {{ initials(issue.assignee.name) }}
      </span>
    </div>

    <div v-if="issue.labels?.length" class="mt-2 flex flex-wrap gap-1">
      <span
        v-for="label in issue.labels"
        :key="label.id"
        class="rounded px-1.5 py-0.5 text-[10px] font-medium text-white"
        :style="{ backgroundColor: label.color || '#6b7280' }"
      >
        {{ label.name }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  issue: { type: Object, required: true },
})

defineEmits(['click'])

const PRIORITY_CLASS = {
  low: 'bg-gray-100 text-gray-700',
  medium: 'bg-blue-100 text-blue-700',
  high: 'bg-orange-100 text-orange-700',
  critical: 'bg-red-100 text-red-700',
}

const priorityClass = computed(
  () => PRIORITY_CLASS[props.issue.priority] || 'bg-gray-100 text-gray-700',
)

const initials = (name) =>
  (name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase()
</script>
