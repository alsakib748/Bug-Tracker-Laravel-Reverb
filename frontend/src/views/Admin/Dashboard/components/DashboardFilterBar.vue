<template>
  <ComponentCard title="Filters" desc="Metrics are scoped to projects you can view. Dates are inclusive (your local day).">
    <div class="flex flex-wrap items-end gap-4">
      <div class="min-w-[16rem] flex-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Projects</label>
        <MultiSelect
          v-model="projectIds"
          :options="projectOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All projects"
          :filter="true"
          class="w-full"
          display="chip"
          :maxSelectedLabels="3"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Start</label>
        <DatePicker
          v-model="start"
          dateFormat="yy-mm-dd"
          placeholder="Default: 30 days ago"
          showIcon
          class="w-40"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
        <DatePicker
          v-model="end"
          dateFormat="yy-mm-dd"
          placeholder="Default: today"
          showIcon
          class="w-40"
        />
      </div>

      <div class="flex items-center gap-2">
        <Button
          label="Apply"
          icon="pi pi-filter"
          :loading="loading"
          @click="apply"
        />
        <Button
          label="Reset"
          icon="pi pi-times"
          severity="secondary"
          outlined
          :disabled="loading"
          @click="reset"
        />
      </div>
    </div>
  </ComponentCard>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import { useProjectStore } from '@/stores/projects'
import { useDashboardStore } from '@/stores/dashboard'

const emit = defineEmits(['apply', 'reset'])

const projectStore = useProjectStore()
const dashboardStore = useDashboardStore()

const projectIds = ref([...dashboardStore.filters.project_ids])
const start = ref(dashboardStore.filters.start ? new Date(dashboardStore.filters.start) : null)
const end = ref(dashboardStore.filters.end ? new Date(dashboardStore.filters.end) : null)

const loading = computed(() => dashboardStore.loading)

const projectOptions = computed(() =>
  projectStore.projects.map((project) => ({ label: project.name, value: project.id })),
)

watch(
  () => dashboardStore.filters,
  (filters) => {
    projectIds.value = [...filters.project_ids]
    start.value = filters.start ? new Date(filters.start) : null
    end.value = filters.end ? new Date(filters.end) : null
  },
  { deep: true },
)

if (!projectStore.projects.length) {
  projectStore.fetchAllProjects().catch(() => {})
}

const toDateString = (value) => {
  if (!value) return null
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const apply = () => {
  dashboardStore.setFilters({
    project_ids: [...projectIds.value],
    start: toDateString(start.value),
    end: toDateString(end.value),
  })
  emit('apply')
}

const reset = () => {
  projectIds.value = []
  start.value = null
  end.value = null
  dashboardStore.setFilters({ project_ids: [], start: null, end: null })
  emit('reset')
}
</script>
