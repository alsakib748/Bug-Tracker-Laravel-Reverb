<template>
  <div class="space-y-6">
    <div class="h-24 animate-pulse rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" />

    <div class="grid animate-pulse grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
      <div v-for="i in 6" :key="i" class="h-24 rounded-2xl bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="h-96 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-800 lg:col-span-2" />
      <div class="h-96 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="h-64 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-800" />
      <div class="h-64 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="h-96 animate-pulse rounded-2xl bg-gray-200 dark:bg-gray-800" />
  </div>
</template>

<script setup>
</script>
