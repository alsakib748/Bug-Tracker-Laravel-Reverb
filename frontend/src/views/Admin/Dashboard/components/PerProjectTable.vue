<template>
  <ComponentCard
    title="Per-project breakdown"
    desc="Open and resolved counts per project across the selected period."
  >
    <div v-if="!rows.length" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
      No projects match the current filters.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left dark:border-gray-800">
            <th class="py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">Project</th>
            <th class="py-2 pr-4 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
            <th class="py-2 pr-4 text-right font-medium text-gray-500 dark:text-gray-400">Open</th>
            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">
              Resolved (period)
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.project_id"
            class="border-b border-gray-100 last:border-0 dark:border-gray-800/50"
          >
            <td class="py-3 pr-4 font-medium text-gray-800 dark:text-white/90">
              <router-link
                :to="{ name: 'ProjectsShow', params: { id: row.project_id } }"
                class="hover:text-brand-500"
              >
                {{ row.name }}
                <span
                  v-if="row.code"
                  class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                >
                  {{ row.code }}
                </span>
              </router-link>
            </td>
            <td class="py-3 pr-4 text-right text-gray-700 dark:text-gray-300">{{ row.total_issues }}</td>
            <td class="py-3 pr-4 text-right text-gray-700 dark:text-gray-300">{{ row.open_issues }}</td>
            <td class="py-3 text-right text-gray-700 dark:text-gray-300">{{ row.resolved_in_period }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </ComponentCard>
</template>

<script setup>
import ComponentCard from '@/components/common/ComponentCard.vue'

defineProps({
  rows: { type: Array, default: () => [] },
})
</script>
