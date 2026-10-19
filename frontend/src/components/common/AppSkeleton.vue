<template>
  <div role="status" aria-live="polite" :class="['animate-pulse space-y-3', className]">
    <slot>
      <div class="h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="h-4 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="h-4 w-5/6 rounded bg-gray-200 dark:bg-gray-700"></div>
    </slot>
  </div>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: '',
  },
})
</script>
