<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div class="space-y-5 sm:space-y-6">
      <!-- Toolbar -->
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

        <Select
          v-model="sprintFilter"
          :options="sprintOptions"
          optionLabel="name"
          optionValue="id"
          placeholder="All issues"
          class="w-48"
          @change="reloadBoard"
        />

        <div class="ml-auto flex flex-wrap items-center gap-2">
          <template v-if="hasPermission('manage board')">
            <template v-if="activeSprint">
              <span class="text-sm text-gray-500 dark:text-gray-400">
                Active:
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ activeSprint.name }}</span>
              </span>
              <Button label="Complete Sprint" size="small" @click="completionVisible = true" />
              <Button label="Cancel Sprint" size="small" severity="danger" outlined @click="confirmCancel" />
            </template>

            <template v-else-if="plannedSprints.length">
              <Select
                v-model="sprintToStart"
                :options="plannedSprints"
                optionLabel="name"
                optionValue="id"
                placeholder="Start a sprint"
                class="w-48"
              />
              <Button
                label="Start Sprint"
                size="small"
                :disabled="!sprintToStart"
                @click="confirmStart"
              />
            </template>

            <Button label="New Sprint" size="small" outlined @click="planningVisible = true" />
          </template>
        </div>
      </div>

      <div v-if="!projectId" class="rounded-xl border border-gray-200 p-8 text-center text-gray-500 dark:border-gray-700">
        Select a project to view its board.
      </div>

      <div v-else-if="boardStore.loading" class="p-8">
        <AppSkeleton class="space-y-3" />
      </div>

      <KanbanBoard
        v-else
        :columns="boardStore.columns"
        @issue-click="openIssue"
        @move="onMove"
      />
    </div>

    <SprintPlanningDialog
      v-model:visible="planningVisible"
      :saving="sprintStore.saving"
      @save="onSprintSaved"
    />

    <SprintCompletionDialog
      v-model:visible="completionVisible"
      :sprint="activeSprint"
      :planned-sprints="plannedSprints"
      :saving="sprintStore.saving"
      @complete="onSprintCompleted"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'

import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import KanbanBoard from '@/components/board/KanbanBoard.vue'
import SprintPlanningDialog from '@/components/sprints/SprintPlanningDialog.vue'
import SprintCompletionDialog from '@/components/sprints/SprintCompletionDialog.vue'

import Select from 'primevue/select'
import Button from 'primevue/button'

import { useBoardStore } from '@/stores/board'
import { useSprintStore } from '@/stores/sprints'
import { useProjectStore } from '@/stores/projects'
import { usePermissions } from '@/composables/usePermissions'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import echo from '@/services/echo'
import { onRealtimeReconnect, debounce } from '@/services/realtime'

const router = useRouter()
const boardStore = useBoardStore()
const sprintStore = useSprintStore()
const projectStore = useProjectStore()
const { hasPermission } = usePermissions()

const currentPageTitle = ref('Kanban Board')

const projectId = ref(null)
const sprintFilter = ref(null)
const sprintToStart = ref(null)
const planningVisible = ref(false)
const completionVisible = ref(false)

const projects = computed(() => projectStore.projects)
const activeSprint = computed(() => sprintStore.activeSprint)
const plannedSprints = computed(() => sprintStore.plannedSprints)
const sprintOptions = computed(() => [
  { id: null, name: 'All issues' },
  ...sprintStore.sprints.map((sprint) => ({ id: sprint.id, name: sprint.name })),
])

let subscribedProjectId = null
let removeReconnectListener = null

const reloadFromRealtime = debounce(() => {
  if (!projectId.value) return
  boardStore.loadBoard(projectId.value, sprintFilter.value)
  sprintStore.loadSprints(projectId.value)
})

const subscribeToProject = (id) => {
  if (subscribedProjectId && subscribedProjectId !== id) {
    echo.leave(`project.${subscribedProjectId}`)
  }
  if (!id) return

  subscribedProjectId = id
  echo
    .private(`project.${id}`)
    .listen('.issue.board.updated', reloadFromRealtime)
    .listen('.issue.created', reloadFromRealtime)
    .listen('.issue.updated', reloadFromRealtime)
    .listen('.issue.deleted', reloadFromRealtime)
}

const onProjectChange = async (event) => {
  projectId.value = event.value
  sprintFilter.value = null
  sprintToStart.value = null
  subscribeToProject(projectId.value)
  await sprintStore.loadSprints(projectId.value)
  await boardStore.loadBoard(projectId.value)
}

const reloadBoard = () => {
  if (projectId.value) {
    boardStore.loadBoard(projectId.value, sprintFilter.value)
  }
}

const openIssue = (issue) => {
  router.push(`/issues/${issue.id}`)
}

const onMove = async ({ issue, status, position }) => {
  if (!hasPermission('manage board')) {
    Swal.fire('Not allowed', 'You do not have permission to move issues on the board.', 'error')
    reloadBoard()
    return
  }
  try {
    await boardStore.persistMove(issue, status, position)
  } catch (error) {
    Swal.fire(
      'Move failed',
      error.response?.data?.message || 'That move is not allowed.',
      'error',
    )
  }
}

const confirmStart = async () => {
  const sprint = plannedSprints.value.find((item) => item.id === sprintToStart.value)
  const result = await Swal.fire({
    title: 'Start sprint?',
    text: `Start "${sprint?.name}"?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Start',
  })
  if (!result.isConfirmed) return

  try {
    await sprintStore.start(sprintToStart.value, projectId.value)
    sprintFilter.value = sprintToStart.value
    sprintToStart.value = null
    await boardStore.loadBoard(projectId.value, sprintFilter.value)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Could not start sprint.', 'error')
  }
}

const confirmCancel = async () => {
  const result = await Swal.fire({
    title: 'Cancel sprint?',
    text: 'Unfinished issues return to the backlog.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Cancel sprint',
    confirmButtonColor: '#d33',
  })
  if (!result.isConfirmed) return

  await sprintStore.cancel(activeSprint.value.id, projectId.value)
  sprintFilter.value = null
  await boardStore.loadBoard(projectId.value)
}

const onSprintSaved = async (payload) => {
  try {
    await sprintStore.create(projectId.value, payload)
    planningVisible.value = false
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Could not create sprint.', 'error')
  }
}

const onSprintCompleted = async ({ carry_over, target_sprint_id }) => {
  try {
    await sprintStore.complete(
      activeSprint.value.id,
      { carry_over, target_sprint_id },
      projectId.value,
    )
    completionVisible.value = false
    sprintFilter.value = null
    await boardStore.loadBoard(projectId.value)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Could not complete sprint.', 'error')
  }
}

onMounted(async () => {
  removeReconnectListener = onRealtimeReconnect(reloadFromRealtime)
  await projectStore.fetchAllProjects()
  if (projects.value.length) {
    projectId.value = projects.value[0].id
    subscribeToProject(projectId.value)
    await sprintStore.loadSprints(projectId.value)
    await boardStore.loadBoard(projectId.value)
  }
})

onUnmounted(() => {
  if (removeReconnectListener) removeReconnectListener()
  if (subscribedProjectId) echo.leave(`project.${subscribedProjectId}`)
})
</script>
