<template>
  <div class="space-y-6" role="status" aria-live="polite" aria-label="Loading dashboard">
    <div class="h-24 animate-pulse rounded-card border border-line bg-surface" />

    <div class="grid animate-pulse grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
      <div v-for="i in 6" :key="i" class="h-24 rounded-card bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="h-96 animate-pulse rounded-card bg-gray-200 dark:bg-gray-800 lg:col-span-2" />
      <div class="h-96 animate-pulse rounded-card bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="h-64 animate-pulse rounded-card bg-gray-200 dark:bg-gray-800" />
      <div class="h-64 animate-pulse rounded-card bg-gray-200 dark:bg-gray-800" />
    </div>

    <div class="h-96 animate-pulse rounded-card bg-gray-200 dark:bg-gray-800" />
  </div>
</template>

<script setup></script>
