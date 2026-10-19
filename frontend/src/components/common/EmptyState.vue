<template>
  <div class="flex flex-col items-center justify-center py-10 text-center" role="status">
    <slot name="icon">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-12 w-12 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
    </slot>
    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
    <p class="mt-2 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'No data',
  },
  description: {
    type: String,
    default: 'There is nothing here yet.',
  },
})
</script>
