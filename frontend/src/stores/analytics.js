import { defineStore } from 'pinia'
import {
  fetchGitHubAnalytics,
  fetchOverview,
  fetchTrends,
  fetchWorkflow,
} from '@/services/analyticsApi'
import { fetchInsight, fetchInsights, requestInsight } from '@/services/insightsApi'

export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    overview: null,
    trends: null,
    workflow: null,
    github: null,
    insights: [],
    insightsPagination: { current_page: 1, last_page: 1, total: 0 },
    loading: false,
    saving: false,
    filters: {
      start: null,
      end: null,
      granularity: 'day',
    },
  }),
  getters: {
    hasPendingInsights: (state) =>
      state.insights.some((insight) => ['queued', 'running'].includes(insight.status)),
  },
  actions: {
    async load(projectId) {
      this.loading = true
      try {
        const params = {
          start: this.filters.start,
          end: this.filters.end,
        }

        const [overview, trends, workflow, github] = await Promise.all([
          fetchOverview(projectId, params),
          fetchTrends(projectId, { ...params, granularity: this.filters.granularity }),
          fetchWorkflow(projectId, params),
          fetchGitHubAnalytics(projectId, params),
        ])

        this.overview = overview.data
        this.trends = trends.data
        this.workflow = workflow.data
        this.github = github.data
      } finally {
        this.loading = false
      }
    },

    async loadInsights(projectId) {
      const { data } = await fetchInsights(projectId)
      this.insights = data.data
      // ResourceCollection + paginator nests paging under `meta`
      const meta = data.meta || data
      this.insightsPagination = {
        current_page: meta.current_page || 1,
        last_page: meta.last_page || 1,
        total: meta.total || 0,
      }
      return this.insights
    },

    async requestInsight(projectId, payload) {
      this.saving = true
      try {
        const { data } = await requestInsight(projectId, payload)
        await this.loadInsights(projectId)
        return data.data
      } finally {
        this.saving = false
      }
    },

    async refreshInsight(insightId) {
      const { data } = await fetchInsight(insightId)
      const index = this.insights.findIndex((insight) => insight.id === insightId)

      if (index !== -1) {
        this.insights.splice(index, 1, data.data)
      }

      return data.data
    },
  },
})
