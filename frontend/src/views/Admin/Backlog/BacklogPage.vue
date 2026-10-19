<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="currentPageTitle"
      description="Prioritize issues and plan them into upcoming sprints."
    />

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

        <div v-if="canManage" class="ml-auto">
          <AppButton size="sm" variant="outline" :onClick="() => (planningVisible = true)">
            New Sprint
          </AppButton>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <!-- Backlog -->
        <div class="lg:col-span-2">
          <ComponentCard title="Backlog">
            <template #header>
              <span class="text-sm text-ink-muted dark:text-gray-400"
                >{{ backlog.length }} issues</span
              >
            </template>

            <AppSkeleton v-if="loading" :rows="4" />

            <ErrorState
              v-else-if="loadError"
              title="Couldn't load the backlog"
              :description="loadError"
              @retry="loadBacklog"
            />

            <EmptyState
              v-else-if="!backlog.length"
              title="The backlog is empty"
              description="Issues that have not been planned into a sprint show up here."
            />

            <draggable
              v-else
              v-model="backlog"
              item-key="id"
              class="space-y-2"
              ghost-class="opacity-40"
              :disabled="!canManage"
              @change="onReorder"
            >
              <template #item="{ element }">
                <div
                  class="flex items-center gap-3 rounded-card border border-line bg-surface p-3 dark:border-gray-700 dark:bg-gray-900/40"
                >
                  <i v-if="canManage" class="pi pi-bars cursor-grab text-gray-400"></i>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-mono text-xs text-ink-muted dark:text-gray-400">{{
                        element.issue_key
                      }}</span>
                      <AppBadge size="sm" :color="priorityBadgeColor(element.priority)">
                        {{ element.priority_label }}
                      </AppBadge>
                    </div>
                    <router-link
                      :to="`/issues/${element.id}`"
                      class="mt-0.5 block truncate text-sm font-medium text-ink hover:text-blue-600 dark:text-gray-100"
                    >
                      {{ element.title }}
                    </router-link>
                  </div>

                  <span
                    v-if="element.story_points"
                    class="text-xs text-ink-muted dark:text-gray-400"
                  >
                    {{ element.story_points }} SP
                  </span>

                  <Select
                    v-if="canManage && plannedSprints.length"
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
          </ComponentCard>
        </div>

        <!-- Sprints summary -->
        <div>
          <ComponentCard title="Sprints">
            <ul
              v-if="sprintStore.sprints.length"
              class="divide-y divide-gray-100 dark:divide-gray-700"
            >
              <li
                v-for="sprint in sprintStore.sprints"
                :key="sprint.id"
                class="flex items-center justify-between py-3 text-sm"
              >
                <span class="truncate text-ink dark:text-gray-200">{{ sprint.name }}</span>
                <AppBadge size="sm" :color="sprintStatusColor(sprint.status)">
                  {{ sprint.status_label }}
                </AppBadge>
              </li>
            </ul>
            <EmptyState
              v-else
              title="No sprints yet"
              description="Create a sprint to start planning your team's work."
            />
          </ComponentCard>
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
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'
import draggable from 'vuedraggable'

import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import SprintPlanningDialog from '@/components/sprints/SprintPlanningDialog.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'

import Select from 'primevue/select'

import { useProjectStore } from '@/stores/projects'
import { useSprintStore } from '@/stores/sprints'
import { usePermissions } from '@/composables/usePermissions'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import { priorityBadgeColor } from '@/utils/issueMeta'
import { fetchBacklog, reorderBacklogIssue } from '@/services/backlogApi'
import echo from '@/services/echo'
import { onRealtimeReconnect, debounce } from '@/services/realtime'

const projectStore = useProjectStore()
const sprintStore = useSprintStore()
const { hasPermission } = usePermissions()
const canManage = computed(() => hasPermission('manage backlog'))

const { projects } = storeToRefs(projectStore)
const { plannedSprints } = storeToRefs(sprintStore)

const currentPageTitle = ref('Backlog')
const projectId = ref(null)
const backlog = ref([])
const selectedSprints = ref({})
const loading = ref(false)
const loadError = ref(null)
const planningVisible = ref(false)

const SPRINT_STATUS_COLOR = {
  planned: 'light',
  active: 'success',
  completed: 'primary',
  cancelled: 'error',
}

const sprintStatusColor = (status) => SPRINT_STATUS_COLOR[status] || 'light'

const loadBacklog = async () => {
  if (!projectId.value) return
  loading.value = true
  loadError.value = null
  try {
    const response = await fetchBacklog(projectId.value)
    backlog.value = response.data.data
  } catch (error) {
    loadError.value =
      error.response?.data?.message || 'Could not load the backlog. Please try again.'
  } finally {
    loading.value = false
  }
}

let subscribedProjectId = null

const refreshFromRealtime = debounce(() => {
  loadBacklog()
  if (projectId.value) sprintStore.loadSprints(projectId.value)
})

const subscribeToProject = (id) => {
  if (subscribedProjectId && subscribedProjectId !== id) {
    echo.leave(`project.${subscribedProjectId}`)
  }
  if (!id) return

  subscribedProjectId = id
  echo
    .private(`project.${id}`)
    .listen('.issue.created', refreshFromRealtime)
    .listen('.issue.updated', refreshFromRealtime)
    .listen('.issue.deleted', refreshFromRealtime)
    .listen('.issue.board.updated', refreshFromRealtime)
}

const onProjectChange = async (event) => {
  projectId.value = event.value
  selectedSprints.value = {}
  subscribeToProject(projectId.value)
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

let removeReconnectListener = null

onMounted(async () => {
  removeReconnectListener = onRealtimeReconnect(refreshFromRealtime)
  await projectStore.fetchAllProjects()
  if (projects.value.length) {
    projectId.value = projects.value[0].id
    subscribeToProject(projectId.value)
    await sprintStore.loadSprints(projectId.value)
    await loadBacklog()
  }
})

onUnmounted(() => {
  if (removeReconnectListener) removeReconnectListener()
  if (subscribedProjectId) echo.leave(`project.${subscribedProjectId}`)
})
</script>
