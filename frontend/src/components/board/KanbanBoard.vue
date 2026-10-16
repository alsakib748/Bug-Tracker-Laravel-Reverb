<template>
  <div class="flex gap-4 overflow-x-auto pb-4">
    <BoardColumn
      v-for="column in columns"
      :key="column.status"
      :column="column"
      @issue-click="$emit('issue-click', $event)"
      @move="$emit('move', $event)"
    />
  </div>
</template>

<script setup>
import BoardColumn from './BoardColumn.vue'

defineProps({
  columns: { type: Array, required: true },
})

defineEmits(['issue-click', 'move'])
</script>
