<template>
  <div
    class="cursor-grab rounded-card border border-line bg-surface p-3 shadow-theme-xs transition hover:border-brand-300 hover:shadow-theme-sm active:cursor-grabbing dark:hover:border-brand-500/40"
    @click="$emit('click', issue)"
  >
    <div class="flex items-center justify-between gap-2">
      <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ issue.issue_key }}</span>
      <span
        v-if="issue.story_points"
        class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"
      >
        {{ issue.story_points }} SP
      </span>
    </div>

    <p class="mt-1 text-sm font-medium text-ink dark:text-gray-100">
      {{ issue.title }}
    </p>

    <div class="mt-2 flex items-center justify-between gap-2">
      <AppBadge size="sm" :color="priorityBadgeColor(issue.priority)">
        {{ issue.priority_label }}
      </AppBadge>
      <span
        v-if="issue.assignee"
        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white"
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
import AppBadge from '@/components/ui/AppBadge.vue'
import { priorityBadgeColor } from '@/utils/issueMeta'

defineProps({
  issue: { type: Object, required: true },
})

defineEmits(['click'])

const initials = (name) =>
  (name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase()
</script>
