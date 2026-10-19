<template>
  <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
      <h1 class="text-page-title font-semibold text-ink dark:text-white/90">{{ pageTitle }}</h1>
      <p v-if="description" class="mt-1 text-sm text-ink-muted dark:text-gray-400">
        {{ description }}
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
      <slot name="actions" />
      <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm">
          <li v-for="(crumb, index) in trail" :key="index" class="flex items-center gap-1.5">
            <svg
              v-if="index > 0"
              class="stroke-current shrink-0 text-gray-400 dark:text-gray-500"
              width="17"
              height="16"
              viewBox="0 0 17 16"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
            >
              <path
                d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                stroke-width="1.2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            <router-link
              v-if="crumb.to"
              :to="crumb.to"
              class="text-ink-muted transition-colors hover:text-ink dark:text-gray-400 dark:hover:text-white/90"
            >
              {{ crumb.label }}
            </router-link>
            <span v-else class="font-medium text-ink dark:text-white/90" aria-current="page">
              {{ crumb.label }}
            </span>
          </li>
        </ol>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  pageTitle: { type: String, required: true },
  description: { type: String, default: '' },
  breadcrumbs: { type: Array, default: () => [] },
})

const trail = computed(() => [
  { label: 'Home', to: '/' },
  ...props.breadcrumbs,
  { label: props.pageTitle },
])
</script>
