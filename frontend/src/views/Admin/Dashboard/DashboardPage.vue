<template>
  <AdminLayout>
    <PageBreadcrumb
      page-title="Dashboard"
      description="Project health, workload, and what needs your attention."
    />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <p v-if="store.data.period" class="text-sm text-gray-500 dark:text-gray-400">
        {{ store.data.period.start }} → {{ store.data.period.end }} ·
        {{ scopeLabel }}
      </p>

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-50 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
        :disabled="store.loading"
        @click="refresh"
      >
        <svg
          class="h-4 w-4"
          :class="{ 'animate-spin': store.loading }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M23 4v6h-6" />
          <path d="M1 20v-6h6" />
          <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10" />
          <path d="M23 14a9 9 0 0 1-14.85 3.36L1 14" />
        </svg>
        Refresh
      </button>
    </div>

    <div class="space-y-6">
      <DashboardFilterBar @apply="refresh" @reset="resetFilters" />

      <!-- Loading skeleton -->
      <DashboardSkeleton v-if="store.loading && !hasData" />

      <!-- Empty state -->
      <EmptyState
        v-else-if="!store.loading && !hasData"
        title="No data yet"
        description="Create a project and log some issues to see your metrics here."
        icon="inbox"
      />

      <!-- Error state -->
      <ErrorState
        v-else-if="store.error && !hasData"
        title="Could not load the dashboard"
        description="Please try again in a moment."
        @retry="refresh"
      />

      <template v-else>
        <!-- 2. KPI cards (drill-down into Issues/Index) -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
          <KpiCard
            label="Total issues"
            :value="store.data.summary.total_issues"
            :to="{ name: 'IssuesIndex' }"
          />
          <KpiCard
            label="Open"
            :value="store.data.summary.open_issues"
            :to="{ name: 'IssuesIndex', query: { status: openStatusQuery } }"
          />
          <KpiCard
            label="Created (period)"
            :value="store.data.summary.created_in_period"
            :to="{ name: 'IssuesIndex' }"
          />
          <KpiCard
            label="Resolved (period)"
            :value="store.data.summary.resolved_in_period"
            :to="{ name: 'IssuesIndex', query: { status: resolvedStatusQuery } }"
          />
          <KpiCard
            label="Overdue"
            :value="store.data.summary.overdue_issues"
            tone="danger"
            :to="{ name: 'IssuesIndex', query: { view: 'overdue' } }"
          />
          <KpiCard
            label="Critical open"
            :value="store.data.summary.critical_open_issues"
            tone="danger"
            :to="{ name: 'IssuesIndex', query: { priority: 'critical' } }"
          />
        </div>

        <!-- 3. Trends + resolution health -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <TrendsChart :series="store.data.trends.series" class="lg:col-span-2" />
          <ResolutionHealthCard
            :resolution="store.data.resolution"
            :reopened-count="store.data.reopened_count"
          />
        </div>

        <!-- 4. Distributions -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <DistributionCard
            title="Status distribution"
            :rows="store.data.status_distribution"
            label-key="status"
            :total="store.data.summary.total_issues"
          />
          <DistributionCard
            title="Priority distribution"
            :rows="store.data.priority_distribution"
            label-key="priority"
            :total="store.data.summary.total_issues"
          />
        </div>

        <!-- 5. Per-project breakdown (drill-down into project issues) -->
        <PerProjectTable :rows="store.data.per_project" />
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useAuthStore } from '@/stores/auth'
import DashboardFilterBar from './components/DashboardFilterBar.vue'
import DashboardSkeleton from './components/DashboardSkeleton.vue'
import KpiCard from './components/KpiCard.vue'
import TrendsChart from './components/TrendsChart.vue'
import ResolutionHealthCard from './components/ResolutionHealthCard.vue'
import DistributionCard from './components/DistributionCard.vue'
import PerProjectTable from './components/PerProjectTable.vue'
import { onRealtimeReconnect, subscribeToUser, debounce } from '@/services/realtime'
import { runWhenIdle } from '@/utils/idle'

const store = useDashboardStore()
const authStore = useAuthStore()

const hasData = computed(() => Boolean(store.data.period || store.data.summary.total_issues > 0))

const DASHBOARD_ISSUE_EVENTS = [
  'issue.created',
  'issue.updated',
  'issue.deleted',
  'issue.assigned',
  'issue.status.changed',
]

const scopeLabel = computed(() => {
  const ids = store.data.scope?.project_ids || []
  if (!ids.length || store.data.scope?.key === 'all') return 'All projects'
  return `${ids.length} project${ids.length === 1 ? '' : 's'}`
})

const openStatusQuery = 'open,assigned,in_progress,code_review,testing,reopened'
const resolvedStatusQuery = 'resolved,closed'

const refresh = () => store.fetchDashboard().catch(() => {})

const resetFilters = () => {
  store.setFilters({ project_ids: [], start: null, end: null })
  refresh()
}

const debouncedRefresh = debounce(refresh, 600)
const unsubs = ref([])

// One `user.{id}` channel covers every project the user can see, so the
// dashboard no longer opens a websocket channel per project. Each event just
// re-runs the current fetch (with whatever filters are applied).
const clearRealtime = () => {
  unsubs.value.forEach((unsub) => unsub())
  unsubs.value = []
}

const startRealtime = () => {
  clearRealtime()

  const userId = authStore.user?.id
  if (!userId) return

  unsubs.value.push(
    subscribeToUser(userId, DASHBOARD_ISSUE_EVENTS, debouncedRefresh),
    // Refetch after a socket drop so the dashboard does not sit on stale numbers.
    onRealtimeReconnect(debouncedRefresh),
  )
}

onMounted(() => {
  // Fire the dashboard request immediately; it is the page's data.
  refresh()

  // Realtime wiring is non-critical for first paint: it opens a websocket and
  // an auth handshake, so we only start it once the page has loaded and the
  // browser is otherwise idle.
  runWhenIdle(startRealtime, { timeout: 3000 })
})

onBeforeUnmount(clearRealtime)
</script>
