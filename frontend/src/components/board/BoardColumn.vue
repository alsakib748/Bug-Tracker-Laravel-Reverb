<template>
  <div class="flex w-72 flex-shrink-0 flex-col rounded-card bg-gray-50 dark:bg-white/[0.02]">
    <div class="flex items-center justify-between border-b border-line px-3 py-2">
      <div class="flex items-center gap-2">
        <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: dotColor }"></span>
        <span class="text-sm font-semibold text-ink dark:text-gray-200">{{ column.label }}</span>
      </div>
      <span
        class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"
      >
        {{ column.issues.length }}
      </span>
    </div>

    <!-- `column` is a reactive object from the Pinia store; vuedraggable needs a
         real v-model to reorder locally before the move is persisted. -->
    <!-- eslint-disable vue/no-mutating-props -->
    <draggable
      v-model="column.issues"
      :group="{ name: 'board-issues' }"
      item-key="id"
      class="min-h-[8rem] flex-1 space-y-2 overflow-y-auto p-2"
      ghost-class="opacity-40"
      @change="onChange"
    >
      <template #item="{ element }">
        <BoardIssueCard :issue="element" @click="$emit('issue-click', element)" />
      </template>
    </draggable>
    <!-- eslint-enable vue/no-mutating-props -->

    <div
      class="border-t border-line px-3 py-1.5 text-right text-xs text-ink-muted dark:border-gray-700"
    >
      {{ totalPoints }} SP
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import draggable from 'vuedraggable'
import BoardIssueCard from './BoardIssueCard.vue'

const props = defineProps({
  column: { type: Object, required: true },
})

const emit = defineEmits(['issue-click', 'move'])

const STATUS_DOT = {
  open: '#9ca3af',
  assigned: '#3b82f6',
  in_progress: '#eab308',
  code_review: '#a855f7',
  testing: '#f97316',
  resolved: '#22c55e',
  closed: '#4b5563',
  reopened: '#ef4444',
}

// Prefer the color the API sends for this column; fall back to our map.
const dotColor = computed(() => props.column.color || STATUS_DOT[props.column.status] || '#9ca3af')

const totalPoints = computed(() =>
  props.column.issues.reduce((sum, issue) => sum + (issue.story_points || 0), 0),
)

const onChange = (event) => {
  const change = event.added || event.moved
  if (!change) return

  emit('move', {
    issue: change.element,
    status: props.column.status,
    position: change.newIndex,
  })
}
</script>
