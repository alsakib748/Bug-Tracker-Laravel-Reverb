<template>
  <div :class="['rounded-card border border-line bg-surface', className]">
    <!-- Card Header -->
    <div class="flex flex-wrap items-start justify-between gap-3 px-6 py-5">
      <div>
        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
          {{ title }}
        </h3>
        <p v-if="desc" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ desc }}
        </p>
      </div>
      <div v-if="$slots.header" class="flex items-center gap-2">
        <slot name="header"></slot>
      </div>
    </div>

    <!-- Card Body -->
    <div class="p-4 border-t border-gray-100 dark:border-gray-800 sm:p-6">
      <div class="space-y-5">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  className: { type: String, default: '' },
  desc: { type: String, default: '' },
})
</script>
