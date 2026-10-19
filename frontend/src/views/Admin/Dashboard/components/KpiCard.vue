<template>
  <router-link
    :to="to"
    class="block rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-500 hover:shadow-sm dark:border-gray-800 dark:bg-white/[0.03]"
  >
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ label }}</p>
    <p
      class="mt-2 text-2xl font-bold"
      :class="{
        'text-red-600 dark:text-red-400': tone === 'danger',
        'text-green-600 dark:text-green-400': tone === 'success',
        'text-gray-800 dark:text-white/90': tone === 'default',
      }"
    >
      {{ value }}
    </p>
    <p v-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
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
