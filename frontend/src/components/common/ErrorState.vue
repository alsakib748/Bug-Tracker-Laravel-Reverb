<template>
  <div
    class="flex flex-col items-center justify-center py-10 text-center"
    role="alert"
    aria-live="polite"
  >
    <slot name="icon">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-12 w-12 text-red-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
    </slot>
    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
    <p class="mt-2 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
    <div class="mt-4 flex gap-3">
      <slot name="action">
        <button
          v-if="retryable"
          type="button"
          class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
          @click="$emit('retry')"
        >
          Retry
        </button>
      </slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Something went wrong',
  },
  description: {
    type: String,
    default: 'An unexpected error occurred.',
  },
  retryable: {
    type: Boolean,
    default: true,
  },
})
defineEmits(['retry'])
</script>
