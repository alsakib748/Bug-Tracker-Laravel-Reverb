<template>
  <ComponentCard title="Filters" desc="Metrics are scoped to projects you can view. Dates are inclusive (your local day).">
    <div class="flex flex-wrap items-end gap-4">
      <div class="min-w-[16rem] flex-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Projects</label>
        <div ref="dropdownRef" class="relative">
          <button
            type="button"
            class="flex h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-4 text-left text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="listbox"
            @click="toggleOpen"
          >
            <span :class="selectedProjects.length ? 'text-gray-800 dark:text-white/90' : 'text-gray-500 dark:text-gray-400'">
              {{ summary }}
            </span>
            <svg
              class="h-4 w-4 shrink-0 text-gray-500 transition-transform"
              :class="{ 'rotate-180': open }"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <div
            v-if="open"
            class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900"
          >
            <input
              v-model="search"
              type="text"
              placeholder="Search projects"
              class="mb-2 h-9 w-full rounded-md border border-gray-200 bg-transparent px-3 text-sm text-gray-800 placeholder:text-gray-500 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-400 dark:focus:border-brand-800"
            />

            <div class="max-h-56 overflow-y-auto pr-1" role="listbox" aria-multiselectable="true">
              <p v-if="projectsLoading" class="px-2 py-3 text-sm text-gray-500 dark:text-gray-400">
                Loading projects…
              </p>
              <p
                v-else-if="!filteredOptions.length"
                class="px-2 py-3 text-sm text-gray-500 dark:text-gray-400"
              >
                No projects found.
              </p>
              <template v-else>
                <label
                  v-for="option in filteredOptions"
                  :key="option.value"
                  class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/30 dark:border-gray-600 dark:bg-gray-800"
                    :checked="projectIds.includes(option.value)"
                    @change="toggleProject(option.value)"
                  />
                  <span class="truncate">{{ option.label }}</span>
                </label>
              </template>
            </div>

            <div
              v-if="projectIds.length"
              class="mt-2 flex justify-end border-t border-gray-100 pt-2 dark:border-gray-800"
            >
              <button
                type="button"
                class="text-sm font-medium text-brand-500 hover:text-brand-600"
                @click="projectIds = []"
              >
                Clear selection
              </button>
            </div>
          </div>
        </div>
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Start</label>
        <input
          v-model="start"
          type="date"
          placeholder="Default: 30 days ago"
          class="h-11 w-40 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-500 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-400 dark:focus:border-brand-800"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
        <input
          v-model="end"
          type="date"
          placeholder="Default: today"
          class="h-11 w-40 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-500 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-400 dark:focus:border-brand-800"
        />
      </div>

      <div class="flex items-center gap-2">
        <AppButton :loading="loading" @click="apply">Apply</AppButton>
        <AppButton variant="outline" :disabled="loading" @click="reset">Reset</AppButton>
      </div>
    </div>
  </ComponentCard>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useProjectStore } from '@/stores/projects'
import { useDashboardStore } from '@/stores/dashboard'

const emit = defineEmits(['apply', 'reset'])

const projectStore = useProjectStore()
const dashboardStore = useDashboardStore()

const projectIds = ref([...dashboardStore.filters.project_ids])
const start = ref(dashboardStore.filters.start || '')
const end = ref(dashboardStore.filters.end || '')

const loading = computed(() => dashboardStore.loading)

const projectsLoading = ref(false)

// The project list is only needed to populate this dropdown, so it is fetched
// on demand (first open) instead of on page load. That keeps a ~78 KB request
// off the dashboard's critical path.
const ensureProjects = () => {
  if (projectStore.projects.length || projectsLoading.value) return

  projectsLoading.value = true
  projectStore
    .fetchAllProjects()
    .catch(() => {})
    .finally(() => {
      projectsLoading.value = false
    })
}

const projectOptions = computed(() =>
  projectStore.projects.map((project) => ({ label: project.name, value: project.id })),
)

const open = ref(false)
const search = ref('')
const dropdownRef = ref(null)

const filteredOptions = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return projectOptions.value
  return projectOptions.value.filter((option) => option.label.toLowerCase().includes(query))
})

const selectedProjects = computed(() =>
  projectOptions.value.filter((option) => projectIds.value.includes(option.value)),
)

const summary = computed(() => {
  if (!selectedProjects.value.length) return 'All projects'
  if (selectedProjects.value.length <= 3) {
    return selectedProjects.value.map((project) => project.label).join(', ')
  }
  return `${selectedProjects.value.length} projects selected`
})

const toggleOpen = () => {
  open.value = !open.value
  if (open.value) ensureProjects()
}

const toggleProject = (id) => {
  const index = projectIds.value.indexOf(id)
  if (index === -1) {
    projectIds.value.push(id)
  } else {
    projectIds.value.splice(index, 1)
  }
}

const onClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))

watch(
  () => dashboardStore.filters,
  (filters) => {
    projectIds.value = [...filters.project_ids]
    start.value = filters.start || ''
    end.value = filters.end || ''
  },
  { deep: true },
)

const apply = () => {
  open.value = false
  dashboardStore.setFilters({
    project_ids: [...projectIds.value],
    start: start.value || null,
    end: end.value || null,
  })
  emit('apply')
}

const reset = () => {
  projectIds.value = []
  start.value = ''
  end.value = ''
  open.value = false
  dashboardStore.setFilters({ project_ids: [], start: null, end: null })
  emit('reset')
}
</script>
