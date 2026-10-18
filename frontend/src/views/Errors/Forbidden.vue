<template>
  <div
    class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden z-1"
  >
    <CommonGridShape />
    <div class="mx-auto w-full max-w-[472px] text-center">
      <h1
        class="mb-4 font-bold text-gray-800 text-title-md dark:text-white/90 xl:text-title-2xl"
      >
        403
      </h1>

      <i
        class="pi pi-lock text-6xl text-gray-300 dark:text-gray-600"
        aria-hidden="true"
      ></i>

      <p
        class="mt-6 mb-6 text-base text-gray-700 dark:text-gray-400 sm:text-lg"
      >
        You don't have permission to access this page.
      </p>

      <router-link
        to="/dashboard"
        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
      >
        Back to Dashboard
      </router-link>
    </div>
  </div>
</template>

<script setup>
import CommonGridShape from '@/components/common/CommonGridShape.vue'
</script>
