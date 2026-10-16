<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div class="space-y-5 sm:space-y-6">
      <div class="flex flex-wrap items-center gap-3">
        <Select
          v-model="projectId"
          :options="projects"
          optionLabel="name"
          optionValue="id"
          placeholder="Select project"
          class="w-56"
          @change="onProjectChange"
        />

        <div class="ml-auto">
          <Button label="New Sprint" size="small" outlined @click="planningVisible = true" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <!-- Backlog -->
        <div class="lg:col-span-2">
          <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
              <h3 class="font-semibold text-gray-700 dark:text-gray-200">Backlog</h3>
              <span class="text-sm text-gray-400">{{ backlog.length }} issues</span>
            </div>

            <div v-if="loading" class="p-8 text-center text-gray-500">Loading backlog...</div>

            <div v-else-if="!backlog.length" class="p-8 text-center text-gray-400">
              The backlog is empty.
            </div>

            <draggable
              v-else
              v-model="backlog"
              item-key="id"
              class="space-y-2 p-4"
              ghost-class="opacity-40"
              @change="onReorder"
            >
              <template #item="{ element }">
                <div
                  class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/40"
                >
                  <i class="pi pi-bars cursor-grab text-gray-400"></i>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-mono text-xs text-gray-400">{{ element.issue_key }}</span>
                      <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="priorityClass(element)"
                      >
                        {{ element.priority_label }}
                      </span>
                    </div>
                    <router-link
                      :to="`/issues/${element.id}`"
                      class="mt-0.5 block truncate text-sm font-medium text-gray-800 hover:text-blue-600 dark:text-gray-100"
                    >
                      {{ element.title }}
                    </router-link>
                  </div>

                  <span v-if="element.story_points" class="text-xs text-gray-400">
                    {{ element.story_points }} SP
                  </span>

                  <Select
                    v-if="plannedSprints.length"
                    v-model="selectedSprints[element.id]"
                    :options="plannedSprints"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Add to sprint"
                    class="w-40 text-sm"
                    @change="addToSprint(element)"
                  />
                </div>
              </template>
            </draggable>
          </div>
        </div>

        <!-- Sprints summary -->
        <div>
          <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
              <h3 class="font-semibold text-gray-700 dark:text-gray-200">Sprints</h3>
            </div>
            <ul v-if="sprintStore.sprints.length" class="divide-y divide-gray-100 dark:divide-gray-700">
              <li
                v-for="sprint in sprintStore.sprints"
                :key="sprint.id"
                class="flex items-center justify-between px-4 py-3 text-sm"
              >
                <span class="truncate text-gray-700 dark:text-gray-200">{{ sprint.name }}</span>
                <span
                  class="rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="sprintStatusClass(sprint.status)"
                >
                  {{ sprint.status_label }}
                </span>
              </li>
            </ul>
            <p v-else class="p-4 text-center text-sm text-gray-400">No sprints yet.</p>
          </div>
        </div>
      </div>
    </div>

    <SprintPlanningDialog
      v-model:visible="planningVisible"
      :saving="sprintStore.saving"
      @save="onSprintSaved"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'
import draggable from 'vuedraggable'

import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import SprintPlanningDialog from '@/components/sprints/SprintPlanningDialog.vue'

import Select from 'primevue/select'
import Button from 'primevue/button'

import { useProjectStore } from '@/stores/projects'
import { useSprintStore } from '@/stores/sprints'
import { fetchBacklog, reorderBacklogIssue } from '@/services/backlogApi'

const projectStore = useProjectStore()
const sprintStore = useSprintStore()

const { projects } = storeToRefs(projectStore)
const { plannedSprints } = storeToRefs(sprintStore)

const currentPageTitle = ref('Backlog')
const projectId = ref(null)
const backlog = ref([])
const selectedSprints = ref({})
const loading = ref(false)
const planningVisible = ref(false)

const PRIORITY_CLASS = {
  low: 'bg-gray-100 text-gray-700',
  medium: 'bg-blue-100 text-blue-700',
  high: 'bg-orange-100 text-orange-700',
  critical: 'bg-red-100 text-red-700',
}

const priorityClass = (issue) => PRIORITY_CLASS[issue.priority] || 'bg-gray-100 text-gray-700'

const sprintStatusClass = (status) => ({
  'bg-gray-100 text-gray-600': status === 'planned',
  'bg-green-100 text-green-700': status === 'active',
  'bg-blue-100 text-blue-700': status === 'completed',
  'bg-red-100 text-red-700': status === 'cancelled',
})

const loadBacklog = async () => {
  if (!projectId.value) return
  loading.value = true
  try {
    const response = await fetchBacklog(projectId.value)
    backlog.value = response.data.data
  } finally {
    loading.value = false
  }
}

const onProjectChange = async (event) => {
  projectId.value = event.value
  selectedSprints.value = {}
  await sprintStore.loadSprints(projectId.value)
  await loadBacklog()
}

const onReorder = async (event) => {
  if (!event.moved) return
  try {
    await reorderBacklogIssue(event.moved.element.id, event.moved.newIndex)
  } catch {
    Swal.fire('Error', 'Could not save the new order.', 'error')
    await loadBacklog()
  }
}

const addToSprint = async (issue) => {
  const sprintId = selectedSprints.value[issue.id]
  if (!sprintId) return
  try {
    await sprintStore.addIssues(sprintId, [issue.id])
    selectedSprints.value[issue.id] = null
    await loadBacklog()
    await sprintStore.loadSprints(projectId.value)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Could not add issue to sprint.', 'error')
  }
}

const onSprintSaved = async (payload) => {
  try {
    await sprintStore.create(projectId.value, payload)
    planningVisible.value = false
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Could not create sprint.', 'error')
  }
}

onMounted(async () => {
  await projectStore.fetchAllProjects()
  if (projects.value.length) {
    projectId.value = projects.value[0].id
    await sprintStore.loadSprints(projectId.value)
    await loadBacklog()
  }
})
</script>
