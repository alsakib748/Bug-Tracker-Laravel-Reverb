<template>
  <div role="status" aria-live="polite" :class="['animate-pulse space-y-3', className]">
    <slot>
      <div
        v-for="index in rows"
        :key="index"
        :class="
          index === 1
            ? 'h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-700'
            : 'h-4 rounded bg-gray-200 dark:bg-gray-700'
        "
        :style="index === rows && rows > 2 ? 'width: 83%' : ''"
      ></div>
    </slot>
  </div>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: '',
  },
  rows: {
    type: Number,
    default: 3,
  },
})
</script>
