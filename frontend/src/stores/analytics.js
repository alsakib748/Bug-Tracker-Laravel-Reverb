import { defineStore } from 'pinia'
import { fetchOverview, fetchTrends, fetchWorkflow } from '@/services/analyticsApi'
import { fetchInsight, fetchInsights, requestInsight } from '@/services/insightsApi'

export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    overview: null,
    trends: null,
    workflow: null,
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

        const [overview, trends, workflow] = await Promise.all([
          fetchOverview(projectId, params),
          fetchTrends(projectId, { ...params, granularity: this.filters.granularity }),
          fetchWorkflow(projectId, params),
        ])

        this.overview = overview.data
        this.trends = trends.data
        this.workflow = workflow.data
      } finally {
        this.loading = false
      }
    },

    async loadInsights(projectId) {
      const { data } = await fetchInsights(projectId)
      this.insights = data.data
      this.insightsPagination = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
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
