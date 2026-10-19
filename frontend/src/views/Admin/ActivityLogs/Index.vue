<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard
        title="Activity Logs"
        desc="A real-time audit trail of everything happening across your workspace."
      >
        <template #header>
          <div class="flex items-center gap-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500"
            >
              <span class="relative flex h-2 w-2">
                <span
                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"
                ></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-success-500"></span>
              </span>
              Live
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ pagination.total }} {{ pagination.total === 1 ? 'event' : 'events' }}
            </span>
          </div>
        </template>

        <ActivityFilter @filter="applyFilters" />

        <ActivityTimeline :logs="logs" :loading="loading" />

        <div v-if="pagination.last_page > 1" class="flex justify-center">
          <Paginator
            :rows="pagination.per_page"
            :totalRecords="pagination.total"
            :first="(pagination.current_page - 1) * pagination.per_page"
            @page="onPageChange"
          />
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, reactive } from 'vue'
import { useActivityLogStore } from '@/stores/activityLogs'
import echo from '@/services/echo'
import { onRealtimeReconnect, debounce } from '@/services/realtime'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import ActivityFilter from '@/components/activity/ActivityFilter.vue'
import ActivityTimeline from '@/components/activity/ActivityTimeline.vue'
import Paginator from 'primevue/paginator'

const pageTitle = ref('Activity Logs')
const store = useActivityLogStore()
const logs = ref([])
const loading = ref(false)
const pagination = ref(store.pagination)
const filters = reactive({})

const subscribedProjects = new Set()

const refreshFromRealtime = debounce(() => {
  loadLogs(filters)
})

const syncProjectSubscriptions = () => {
  const ids = new Set(logs.value.map((log) => log.project?.id).filter(Boolean))

  ids.forEach((id) => {
    if (subscribedProjects.has(id)) return
    subscribedProjects.add(id)
    echo.private(`project.${id}`).listen('.activity.logged', refreshFromRealtime)
  })

  Array.from(subscribedProjects).forEach((id) => {
    if (!ids.has(id)) {
      echo.leave(`project.${id}`)
      subscribedProjects.delete(id)
    }
  })
}

const loadLogs = async (params = {}) => {
  loading.value = true
  try {
    await store.fetchLogs(params)
    logs.value = store.logs
    pagination.value = store.pagination
    syncProjectSubscriptions()
  } catch (error) {
    console.error('Error loading logs:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = (filterParams) => {
  Object.keys(filters).forEach((key) => delete filters[key])
  Object.assign(filters, filterParams)
  loadLogs(filters)
}

const onPageChange = (event) => {
  const page = event.page + 1
  loadLogs({ ...filters, page })
}

let removeReconnectListener = null

onMounted(() => {
  loadLogs()
  removeReconnectListener = onRealtimeReconnect(refreshFromRealtime)
})

onUnmounted(() => {
  if (removeReconnectListener) removeReconnectListener()
  subscribedProjects.forEach((id) => echo.leave(`project.${id}`))
  subscribedProjects.clear()
})
</script>
