<template>
  <div class="flex w-72 flex-shrink-0 flex-col rounded-xl bg-gray-50 dark:bg-gray-900/40">
    <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-700">
      <div class="flex items-center gap-2">
        <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: dotColor }"></span>
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ column.label }}</span>
      </div>
      <span
        class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"
      >
        {{ column.issues.length }}
      </span>
    </div>

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

    <div class="border-t border-gray-200 px-3 py-1.5 text-right text-xs text-gray-400 dark:border-gray-700">
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

const dotColor = computed(() => STATUS_DOT[props.column.status] || '#9ca3af')

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
