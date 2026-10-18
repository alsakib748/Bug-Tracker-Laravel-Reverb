<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Analytics & Insights" />

    <div class="space-y-5 sm:space-y-6">
      <!-- Filters -->
      <ComponentCard title="Filters" desc="Metrics are always computed from the database for the selected period.">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Project</label>
            <Select v-model="projectId" :options="projects" optionLabel="name" optionValue="id" filter
              placeholder="Select a project" class="w-56" @change="load" />
          </div>
          <div>
            <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Start</label>
            <DatePicker v-model="start" dateFormat="yy-mm-dd" showIcon class="w-44" />
          </div>
          <div>
            <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">End</label>
            <DatePicker v-model="end" dateFormat="yy-mm-dd" showIcon class="w-44" />
          </div>
          <div>
            <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Trend granularity</label>
            <Select v-model="granularity" :options="granularities" class="w-36" />
          </div>
          <button class="px-4 py-2 text-white bg-blue-600 rounded-md disabled:opacity-50" :disabled="!projectId"
            @click="load">
            Apply
          </button>
        </div>
      </ComponentCard>

      <div v-if="!projectId" class="text-sm text-gray-500">
        Select a project to view its analytics and generate AI insights.
      </div>

      <div v-else-if="store.loading" class="py-8 text-center">
        <i class="text-2xl text-blue-600 pi pi-spin pi-spinner"></i>
      </div>

      <template v-else-if="store.overview">
        <!-- KPI cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
          <StatCard label="Total issues" :value="store.overview.summary.total_issues" />
          <StatCard label="Open" :value="store.overview.summary.open_issues" />
          <StatCard label="Created (period)" :value="store.overview.summary.created_in_period" />
          <StatCard label="Resolved (period)" :value="store.overview.summary.resolved_in_period" />
          <StatCard label="Overdue" :value="store.overview.summary.overdue_issues" tone="danger" />
          <StatCard label="Critical open" :value="store.overview.summary.critical_open_issues" tone="danger" />
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <!-- Resolution -->
          <ComponentCard title="Median resolution time">
            <p class="text-3xl font-bold text-gray-800 dark:text-white/90">
              {{ store.overview.resolution.median_hours ?? '—' }}
              <span v-if="store.overview.resolution.median_hours"
                class="text-sm font-normal text-gray-500">hours</span>
            </p>
            <p class="mt-1 text-xs text-gray-500">
              Based on {{ store.overview.resolution.sample_size }} resolved issues
              ({{ store.overview.resolution.coverage }} coverage)
            </p>

            <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
              <p class="text-xs text-gray-500">Reopened in period</p>
              <p class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ store.workflow?.reopened_count ?? 0 }}
              </p>
            </div>
          </ComponentCard>

          <!-- Trends -->
          <ComponentCard title="Created vs resolved" class="lg:col-span-2">
            <VueApexCharts type="line" height="300" :options="chartOptions" :series="chartSeries" />
          </ComponentCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <!-- Status distribution -->
          <ComponentCard title="Status distribution">
            <ul class="space-y-2">
              <li v-for="row in store.overview.status_distribution" :key="row.status"
                class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-700 dark:text-gray-300">{{ row.status }}</span>
                <span class="font-semibold text-gray-900 dark:text-white/90">{{ row.count }}</span>
              </li>
              <li v-if="!store.overview.status_distribution.length" class="text-sm text-gray-500">No data.</li>
            </ul>
          </ComponentCard>

          <!-- Priority distribution -->
          <ComponentCard title="Priority distribution">
            <ul class="space-y-2">
              <li v-for="row in store.overview.priority_distribution" :key="row.priority"
                class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-700 dark:text-gray-300">{{ row.priority }}</span>
                <span class="font-semibold text-gray-900 dark:text-white/90">{{ row.count }}</span>
              </li>
              <li v-if="!store.overview.priority_distribution.length" class="text-sm text-gray-500">No data.</li>
            </ul>
          </ComponentCard>
        </div>

        <!-- Workflow: time in status -->
        <ComponentCard v-if="store.workflow?.time_in_status?.length" title="Median time in status (hours)">
          <ul class="space-y-2">
            <li v-for="row in store.workflow.time_in_status" :key="row.status"
              class="flex items-center justify-between text-sm">
              <span class="capitalize text-gray-700 dark:text-gray-300">{{ row.status }}</span>
              <span class="font-semibold text-gray-900 dark:text-white/90">
                {{ row.median_hours }} h · {{ row.sample_size }} samples
              </span>
            </li>
          </ul>
        </ComponentCard>

        <!-- AI insights -->
        <ComponentCard title="AI-assisted insights"
          desc="The interpretation is AI-generated; every metric and timestamp comes from the database.">
          <div class="flex flex-wrap items-end gap-3">
            <div>
              <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Insight type</label>
              <Select v-model="insightType" :options="insightTypes" optionLabel="label" optionValue="value"
                class="w-56" />
            </div>
            <button class="px-4 py-2 text-white bg-blue-600 rounded-md disabled:opacity-50"
              :disabled="store.saving" @click="requestInsight">
              <i class="mr-1 pi pi-sparkles"></i>
              Generate insight
            </button>
          </div>

          <div class="grid grid-cols-1 gap-4 mt-5 lg:grid-cols-3">
            <!-- History -->
            <div class="lg:col-span-1">
              <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Recent insights</h4>
              <ul class="space-y-2">
                <li v-for="insight in store.insights" :key="insight.id">
                  <button class="w-full p-3 text-left border rounded-md dark:border-gray-800 hover:border-blue-400"
                    :class="{ 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10': selectedInsight?.id === insight.id }"
                    @click="selectedInsight = insight">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-800 capitalize dark:text-gray-200">
                        {{ insight.insight_type.replace('_', ' ') }}
                      </span>
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="statusClass(insight.status)">
                        {{ insight.status }}
                      </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                      {{ insight.period_start }} &rarr; {{ insight.period_end }}
                    </p>
                  </button>
                </li>
                <li v-if="!store.insights.length" class="text-sm text-gray-500">
                  No insights generated yet.
                </li>
              </ul>
            </div>

            <!-- Detail -->
            <div class="lg:col-span-2">
              <div v-if="!selectedInsight" class="text-sm text-gray-500">
                Select an insight to see its interpretation.
              </div>

              <div v-else-if="isPending(selectedInsight)"
                class="py-8 text-center text-sm text-gray-500">
                <i class="mr-2 pi pi-spin pi-spinner"></i>
                Generating insight…
              </div>

              <div v-else-if="selectedInsight.status === 'failed'"
                class="p-3 text-sm text-red-700 bg-red-50 rounded-md dark:bg-red-900/20 dark:text-red-300">
                Insight generation failed ({{ selectedInsight.error_code || 'unknown error' }}).
              </div>

              <div v-else-if="selectedInsight.result" class="space-y-4">
                <div>
                  <h4 class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Summary</h4>
                  <p class="text-sm text-gray-800 dark:text-gray-200">{{ selectedInsight.result.summary }}</p>
                </div>

                <div v-if="selectedInsight.result.findings?.length">
                  <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Findings</h4>
                  <div class="space-y-2">
                    <div v-for="(finding, index) in selectedInsight.result.findings" :key="index"
                      class="p-3 border rounded-md dark:border-gray-800">
                      <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ finding.title }}</span>
                        <span class="px-2 py-0.5 text-xs rounded-full" :class="confidenceClass(finding.confidence)">
                          {{ finding.confidence }}
                        </span>
                      </div>
                      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ finding.interpretation }}</p>
                      <div v-if="finding.evidence?.length" class="flex flex-wrap gap-1 mt-2">
                        <span v-for="metric in finding.evidence" :key="metric"
                          class="px-2 py-0.5 text-xs font-mono bg-gray-100 rounded dark:bg-gray-800 dark:text-gray-300">
                          {{ metric }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="selectedInsight.result.recommendations?.length">
                  <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Suggested human review</h4>
                  <ul class="space-y-2">
                    <li v-for="(rec, index) in selectedInsight.result.recommendations" :key="index"
                      class="p-3 border rounded-md dark:border-gray-800">
                      <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ rec.title }}</p>
                      <p class="text-sm text-gray-600 dark:text-gray-400">{{ rec.reason }}</p>
                    </li>
                  </ul>
                </div>

                <div v-if="selectedInsight.result.limitations?.length">
                  <h4 class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Limitations</h4>
                  <ul class="pl-5 text-sm text-gray-600 list-disc dark:text-gray-400">
                    <li v-for="(limitation, index) in selectedInsight.result.limitations" :key="index">
                      {{ limitation }}
                    </li>
                  </ul>
                </div>

                <p class="text-xs text-gray-400">
                  Generated by {{ selectedInsight.model_provider || 'n/a' }} /
                  {{ selectedInsight.model_name || 'n/a' }}
                  <span v-if="selectedInsight.is_expired"> · expired</span>
                </p>
              </div>
            </div>
          </div>
        </ComponentCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import StatCard from '@/components/analytics/StatCard.vue'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import VueApexCharts from 'vue3-apexcharts'
import { useAnalyticsStore } from '@/stores/analytics'
import { useProjectStore } from '@/stores/projects'
import { toast } from 'vue3-toastify'

const store = useAnalyticsStore()
const projectStore = useProjectStore()

const projects = ref([])
const projectId = ref(null)
const start = ref(null)
const end = ref(null)
const granularity = ref('day')
const insightType = ref('project_summary')
const selectedInsight = ref(null)

const granularities = ['day', 'week', 'month']

const insightTypes = [
  { label: 'Project summary', value: 'project_summary' },
  { label: 'Trend analysis', value: 'trend_analysis' },
  { label: 'Bottleneck analysis', value: 'bottleneck' },
  { label: 'Sprint review', value: 'sprint_review' },
]

const chartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit' },
  colors: ['#465fff', '#12b76a'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  xaxis: { categories: store.trends?.series?.buckets || [] },
  legend: { position: 'top' },
}))

const chartSeries = computed(() => [
  { name: 'Created', data: store.trends?.series?.created || [] },
  { name: 'Resolved', data: store.trends?.series?.resolved || [] },
])

const toIsoDate = (value) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  const pad = (part) => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const isPending = (insight) => ['queued', 'running'].includes(insight.status)

const load = async () => {
  if (!projectId.value) return

  store.filters.start = toIsoDate(start.value)
  store.filters.end = toIsoDate(end.value)
  store.filters.granularity = granularity.value

  try {
    await store.load(projectId.value)
    await store.loadInsights(projectId.value)
  } catch {
    toast.error('Failed to load analytics.')
  }
}

const requestInsight = async () => {
  try {
    const insight = await store.requestInsight(projectId.value, {
      insight_type: insightType.value,
      start: store.filters.start,
      end: store.filters.end,
    })

    selectedInsight.value = insight
    toast.info('Insight queued — this can take a moment.')
    startPolling(insight.id)
  } catch {
    toast.error('Failed to request the insight.')
  }
}

const selectInsight = (insight) => {
  selectedInsight.value = insight
}

let pollTimer = null

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

const startPolling = (insightId) => {
  stopPolling()
  let attempts = 0

  pollTimer = setInterval(async () => {
    attempts += 1

    try {
      const insight = await store.refreshInsight(insightId)

      if (selectedInsight.value?.id === insightId) {
        selectedInsight.value = insight
      }

      if (!isPending(insight) || attempts >= 40) {
        stopPolling()

        if (insight.status === 'completed') {
          toast.success('Insight ready.')
        } else if (insight.status === 'failed') {
          toast.error('Insight generation failed.')
        }
      }
    } catch {
      stopPolling()
    }
  }, 4000)
}

const statusClass = (status) =>
  ({
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    queued: 'bg-amber-100 text-amber-800',
    running: 'bg-blue-100 text-blue-800',
  })[status] || 'bg-gray-100 text-gray-700'

const confidenceClass = (confidence) =>
  ({
    high: 'bg-green-100 text-green-700',
    medium: 'bg-amber-100 text-amber-700',
    low: 'bg-gray-100 text-gray-600',
  })[confidence] || 'bg-gray-100 text-gray-600'

onMounted(async () => {
  await projectStore.fetchAllProjects()
  projects.value = projectStore.projects

  if (projects.value.length) {
    projectId.value = projects.value[0].id
    await load()
  }
})

onBeforeUnmount(stopPolling)
</script>
