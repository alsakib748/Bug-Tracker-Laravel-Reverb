<template>
  <router-link
    :to="to"
    class="block rounded-card border border-line bg-surface p-5 transition hover:border-brand-400 hover:shadow-theme-xs"
  >
    <p class="text-xs text-ink-muted dark:text-gray-400">{{ label }}</p>
    <p
      class="mt-2 text-2xl font-bold"
      :class="{
        'text-error-600 dark:text-error-400': tone === 'danger',
        'text-success-600 dark:text-success-400': tone === 'success',
        'text-ink dark:text-white/90': tone === 'default',
      }"
    >
      {{ value }}
    </p>
    <p v-if="hint" class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ hint }}</p>
  </router-link>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  tone: { type: String, default: 'default' },
  hint: { type: String, default: '' },
  to: { type: [Object, String], required: true },
})
</script>
