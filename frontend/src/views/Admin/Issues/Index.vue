<template>
  <AdminLayout>
    <PageBreadcrumb
      page-title="Issues"
      description="Find, triage, and track issues across your projects."
    >
      <template #actions>
        <AppButton v-if="can('create issues')" size="sm" :onClick="goCreate" :startIcon="PlusIcon">
          New Issue
        </AppButton>
      </template>
    </PageBreadcrumb>

    <!-- Saved views -->
    <div class="mb-5 flex flex-wrap items-center gap-2">
      <button
        v-for="view in savedViews"
        :key="view.key"
        type="button"
        :class="[
          'rounded-full px-3.5 py-1.5 text-sm font-medium transition',
          activeView === view.key
            ? 'bg-brand-500 text-white shadow-theme-xs'
            : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-white/[0.03]',
        ]"
        @click="applyView(view.key)"
      >
        {{ view.label }}
        <span
          v-if="view.key === 'overdue'"
          class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-error-100 px-1 text-[10px] font-semibold text-error-700"
          aria-hidden="true"
        ></span>
      </button>
    </div>

    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="activeViewLabel" :desc="resultSummary">
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
          <div class="relative">
            <i
              class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"
              aria-hidden="true"
            />
            <InputText
              v-model="filters.search"
              placeholder="Search issues…"
              class="w-full !pl-9"
              @input="debouncedApplyFilters"
            />
          </div>
          <Select
            v-model="filters.project_id"
            :options="projects"
            optionLabel="name"
            optionValue="id"
            placeholder="All projects"
            showClear
            @change="applyFilters"
          />
          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All statuses"
            showClear
            @change="applyFilters"
          />
          <Select
            v-model="filters.priority"
            :options="priorityOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All priorities"
            showClear
            @change="applyFilters"
          />
        </div>

        <AppSkeleton v-if="loading && !issues.length" :rows="6" />

        <ErrorState
          v-else-if="loadError"
          title="Couldn't load issues"
          :description="loadError"
          @retry="retry"
        />

        <EmptyState
          v-else-if="!issues.length"
          :title="hasActiveFilters ? 'No issues match your filters' : 'No issues yet'"
          :description="
            hasActiveFilters
              ? 'Try adjusting the search, project, status, or priority filters.'
              : 'Create the first issue to start tracking work for this team.'
          "
        >
          <template #action>
            <AppButton v-if="hasActiveFilters" size="sm" variant="outline" :onClick="clearFilters">
              Clear filters
            </AppButton>
            <AppButton
              v-else-if="can('create issues')"
              size="sm"
              :onClick="goCreate"
              :startIcon="PlusIcon"
            >
              New Issue
            </AppButton>
          </template>
        </EmptyState>

        <DataTable
          v-else
          :value="issues"
          dataKey="id"
          :loading="loading"
          rowHover
          class="w-full"
          @sort="onSort"
          @row-click="onRowClick"
          sortField="id"
          :sortOrder="-1"
        >
          <Column field="id" header="#" sortable style="width: 64px">
            <template #body="{ data }">
              <span class="font-medium text-gray-500 dark:text-gray-400">#{{ data.id }}</span>
            </template>
          </Column>
          <Column field="title" header="Issue" sortable style="min-width: 240px">
            <template #body="{ data }">
              <router-link
                :to="`/issues/${data.id}`"
                class="font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400"
              >
                {{ data.title }}
              </router-link>
              <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                <router-link :to="`/projects/${data.project?.id}`" class="hover:underline">
                  {{ data.project?.name }}
                </router-link>
                <span v-if="isOverdueIssue(data)" class="ml-2 font-semibold text-error-600">
                  Overdue
                </span>
              </div>
            </template>
          </Column>
          <Column field="status" header="Status" sortable style="width: 130px">
            <template #body="{ data }">
              <AppBadge size="sm" :color="statusBadgeColor(data.status)">
                {{ data.status_label }}
              </AppBadge>
            </template>
          </Column>
          <Column field="priority" header="Priority" sortable style="width: 110px">
            <template #body="{ data }">
              <AppBadge size="sm" :color="priorityBadgeColor(data.priority)">
                {{ data.priority_label }}
              </AppBadge>
            </template>
          </Column>
          <Column field="assignee.name" header="Assignee" sortable style="width: 160px">
            <template #body="{ data }">
              <span v-if="data.assignee?.name" class="text-sm text-gray-700 dark:text-gray-300">
                {{ data.assignee.name }}
              </span>
              <span v-else class="text-sm italic text-gray-400 dark:text-gray-500">
                Unassigned
              </span>
            </template>
          </Column>
          <Column field="created_at" header="Created" sortable style="width: 120px">
            <template #body="{ data }">
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(data.created_at) }}
              </span>
            </template>
          </Column>
          <Column header="" style="width: 96px">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-1">
                <router-link
                  v-if="can('update issues')"
                  :to="`/issues/${data.id}/edit`"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-white/5"
                  title="Edit issue"
                >
                  <i class="pi pi-pen-to-square text-sm"></i>
                </router-link>
                <button
                  v-if="can('delete issues')"
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-error-50 hover:text-error-600 dark:text-gray-400 dark:hover:bg-error-500/10"
                  title="Delete issue"
                  @click="confirmDelete(data)"
                >
                  <i class="pi pi-trash text-sm"></i>
                </button>
                <span
                  v-if="!can('update issues') && !can('delete issues')"
                  class="text-xs text-gray-400"
                >
                  View only
                </span>
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Infinite-scroll footer -->
        <div
          v-if="issues.length"
          class="flex items-center justify-center gap-2.5 pt-4 text-sm"
          aria-live="polite"
        >
          <template v-if="loadingMore">
            <i class="pi pi-spin pi-spinner text-brand-500" aria-hidden="true"></i>
            <span class="text-gray-500 dark:text-gray-400">Loading more issues…</span>
          </template>
          <template v-else-if="loadMoreError">
            <span class="text-error-600 dark:text-error-500">{{ loadMoreError }}</span>
            <AppButton size="sm" variant="outline" :onClick="loadMore">Retry</AppButton>
          </template>
          <span v-else-if="!hasMore" class="text-gray-400 dark:text-gray-500">
            You've reached the end
          </span>
        </div>

        <!-- Sentinel: observed to fetch the next page while scrolling -->
        <div ref="sentinel" class="h-px w-full" aria-hidden="true"></div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import { PlusIcon } from '@/icons'

import { ref, computed, onMounted, onUnmounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useIssueStore } from '@/stores/issues'
import { useProjectStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import echo from '@/services/echo'
import { onRealtimeReconnect, debounce } from '@/services/realtime'
import { extractErrorMessage } from '@/utils/errorHandler'
import { statusBadgeColor, priorityBadgeColor, isOverdue, formatDate } from '@/utils/issueMeta'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Swal from 'sweetalert2'

import { storeToRefs } from 'pinia'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()

const issueStore = useIssueStore()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const { issues, loading, loadingMore, pagination } = storeToRefs(issueStore)

const projectStore = useProjectStore()

const projects = ref([])
const loadError = ref(null)
const loadMoreError = ref(null)
const sentinel = ref(null)

const PER_PAGE = 15

const filters = reactive({
  search: '',
  project_id: null,
  status: null,
  priority: null,
  assigned_to: null,
  reporter_id: null,
  overdue: false,
})

// Seed from the query string so dashboard drill-downs arrive pre-filtered.
if (route.query.project_id) filters.project_id = Number(route.query.project_id)
if (route.query.status) filters.status = String(route.query.status)
if (route.query.priority) filters.priority = String(route.query.priority)
if (route.query.search) filters.search = String(route.query.search)

const savedViews = [
  { key: 'all', label: 'All issues' },
  { key: 'mine', label: 'My issues' },
  { key: 'reported', label: 'Reported by me' },
  { key: 'overdue', label: 'Overdue' },
]

const activeView = ref('all')

// The dashboard can deep-link with ?view=mine etc.
if (route.query.view && savedViews.some((v) => v.key === route.query.view)) {
  activeView.value = String(route.query.view)
}

const activeViewLabel = computed(
  () => savedViews.find((v) => v.key === activeView.value)?.label ?? 'All issues',
)

const hasActiveFilters = computed(
  () =>
    activeView.value !== 'all' ||
    !!(
      filters.search ||
      filters.project_id ||
      filters.status ||
      filters.priority ||
      filters.assigned_to ||
      filters.reporter_id ||
      filters.overdue
    ),
)

const resultSummary = computed(() => {
  if (loadError.value) return ''
  const total = pagination.value?.total ?? 0
  const shown = issues.value.length
  if (!total) return ''
  if (shown >= total) return total === 1 ? '1 issue' : `${total} issues`
  return `Showing ${shown} of ${total} issues`
})

const hasMore = computed(() => pagination.value.current_page < pagination.value.last_page)

const statusOptions = [
  { label: 'Open', value: 'open' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Code Review', value: 'code_review' },
  { label: 'Testing', value: 'testing' },
  { label: 'Resolved', value: 'resolved' },
  { label: 'Closed', value: 'closed' },
  { label: 'Reopened', value: 'reopened' },
]

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
]

let lastParams = {}

const subscribedProjects = new Set()

const refreshFromRealtime = debounce(() => {
  // Refetch everything currently loaded in one request so scroll depth is
  // kept, but cap the size so a long scroll session cannot amplify a burst
  // of realtime events into an unbounded single request.
  const loaded = Math.min(Math.max(issues.value.length, PER_PAGE), 50)
  fetchIssues({ ...buildFilterParams(), sort: lastParams.sort || '-id', page: 1, per_page: loaded })
})

// The issue list is project-scoped on the backend, so we mirror the realtime
// events for every project currently visible on the page.
const syncProjectSubscriptions = () => {
  const ids = new Set(issues.value.map((issue) => issue.project?.id).filter(Boolean))

  ids.forEach((id) => {
    if (subscribedProjects.has(id)) return
    subscribedProjects.add(id)
    echo
      .private(`project.${id}`)
      .listen('.issue.created', refreshFromRealtime)
      .listen('.issue.updated', refreshFromRealtime)
      .listen('.issue.assigned', refreshFromRealtime)
      .listen('.issue.deleted', refreshFromRealtime)
  })

  Array.from(subscribedProjects).forEach((id) => {
    if (!ids.has(id)) {
      echo.leave(`project.${id}`)
      subscribedProjects.delete(id)
    }
  })
}

const buildFilterParams = () => ({
  search: filters.search || undefined,
  project_id: filters.project_id || undefined,
  status: filters.status || undefined,
  priority: filters.priority || undefined,
  assigned_to: filters.assigned_to || undefined,
  reporter_id: filters.reporter_id || undefined,
  overdue: filters.overdue ? '1' : undefined,
})

const fetchIssues = async (params = {}, { append = false } = {}) => {
  try {
    lastParams = { ...params }
    const response = await issueStore.fetchIssues(params, { append })
    if (append) loadMoreError.value = null
    else {
      loadError.value = null
      syncProjectSubscriptions()
    }
    return response
  } catch (error) {
    console.error('Error fetching issues: ', error)
    const message = extractErrorMessage(error) || 'Something went wrong while loading issues.'
    if (append) loadMoreError.value = message
    else loadError.value = message
  }
}

// Replace the whole list (used on mount, filter/view/sort changes, retry).
const reload = (extra = {}) =>
  fetchIssues({
    ...buildFilterParams(),
    sort: lastParams.sort || '-id',
    page: 1,
    per_page: PER_PAGE,
    ...extra,
  })

const loadMore = () => {
  if (loading.value || loadingMore.value || !hasMore.value) return
  fetchIssues(
    {
      ...buildFilterParams(),
      sort: lastParams.sort || '-id',
      page: pagination.value.current_page + 1,
      per_page: PER_PAGE,
    },
    { append: true },
  )
}

// If the sentinel is still near the viewport after a fetch (e.g. a short page
// that doesn't fill the screen), keep loading until the viewport is filled.
watch([issues, loadingMore], () => {
  if (loading.value || loadingMore.value || !hasMore.value || !sentinel.value) return
  const rect = sentinel.value.getBoundingClientRect()
  if (rect.top < window.innerHeight + 300) loadMore()
})

const loadProjects = async () => {
  try {
    await projectStore.fetchAllProjects()
    projects.value = projectStore.projects
  } catch (error) {
    console.error('Error loading projects: ', error)
  }
}

const applyView = (key) => {
  activeView.value = key
  filters.assigned_to = null
  filters.reporter_id = null
  filters.overdue = false

  if (key === 'mine') filters.assigned_to = authStore.user?.id || null
  if (key === 'reported') filters.reporter_id = authStore.user?.id || null
  if (key === 'overdue') filters.overdue = true

  reload()
}

const applyFilters = () => {
  reload()
}

const debouncedApplyFilters = debounce(applyFilters, 300)

const clearFilters = () => {
  filters.search = ''
  filters.project_id = null
  filters.status = null
  filters.priority = null
  applyView('all')
}

const retry = () => {
  reload()
}

const goCreate = () => router.push('/issues/create')

const isOverdueIssue = (issue) => isOverdue(issue)

const onRowClick = (event) => {
  const interactive = event.originalEvent?.target?.closest?.('a, button, input, [role="button"]')
  if (interactive) return
  router.push(`/issues/${event.data.id}`)
}

const onSort = (event) => {
  const direction = event.sortOrder === 1 ? '' : '-'
  reload({ sort: direction + event.sortField })
}

const confirmDelete = async (issue) => {
  const result = await Swal.fire({
    title: 'Delete Issue?',
    text: `Delete "${issue.title}"? This cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d92d20',
    cancelButtonColor: '#475467',
    confirmButtonText: 'Yes, delete',
  })
  if (result.isConfirmed) {
    try {
      await issueStore.deleteIssue(issue.id)
      // Remove locally so the user keeps their scroll position.
      issueStore.removeIssueFromList(issue.id)
      Swal.fire('Deleted', 'Issue deleted', 'success')
    } catch (error) {
      Swal.fire('Error', extractErrorMessage(error) || 'Failed to delete.', 'error')
    }
  }
}

let removeReconnectListener = null
let scrollObserver = null

onMounted(async () => {
  if (activeView.value !== 'all') {
    // Apply saved-view constraints on first load as well.
    if (activeView.value === 'mine') filters.assigned_to = authStore.user?.id || null
    if (activeView.value === 'reported') filters.reporter_id = authStore.user?.id || null
    if (activeView.value === 'overdue') filters.overdue = true
  }

  loadProjects()
  reload()
  removeReconnectListener = onRealtimeReconnect(refreshFromRealtime)

  // Fetch the next page when the sentinel scrolls into view (with a 300px
  // lead so the next page is already arriving as the user reaches the end).
  scrollObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) loadMore()
    },
    { rootMargin: '300px 0px' },
  )
  if (sentinel.value) scrollObserver.observe(sentinel.value)
})

onUnmounted(() => {
  if (scrollObserver) scrollObserver.disconnect()
  if (removeReconnectListener) removeReconnectListener()
  subscribedProjects.forEach((id) => echo.leave(`project.${id}`))
  subscribedProjects.clear()
})
</script>
