import { defineStore } from 'pinia'
import api from '@/services/api'

const emptySummary = () => ({
  total_issues: 0,
  open_issues: 0,
  created_in_period: 0,
  resolved_in_period: 0,
  overdue_issues: 0,
  critical_open_issues: 0,
})

const emptyTrends = () => ({ granularity: 'day', series: { buckets: [], created: [], resolved: [] } })

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    data: {
      period: null,
      scope: { project_ids: [], key: 'all' },
      summary: emptySummary(),
      resolution: { median_hours: null, sample_size: 0, legacy_sample_size: 0, coverage: 'unknown' },
      status_distribution: [],
      priority_distribution: [],
      reopened_count: 0,
      trends: emptyTrends(),
      per_project: [],
      metric_definition_version: 'v1',
    },
    filters: {
      project_ids: [],
      start: null,
      end: null,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    },
    loading: false,
    error: null,
    lastFetchedAt: null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true
      this.error = null

      try {
        const params = {}

        if (this.filters.project_ids?.length) {
          params.project_ids = this.filters.project_ids
        }
        if (this.filters.start) {
          params.start = this.filters.start
        }
        if (this.filters.end) {
          params.end = this.filters.end
        }
        if (this.filters.timezone) {
          params.timezone = this.filters.timezone
        }

        const response = await api.get('/dashboard', { params })
        this.data = response.data
        this.lastFetchedAt = Date.now()
        return response.data
      } catch (error) {
        this.error = error
        console.error('Failed to fetch dashboard', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilters(partial = {}) {
      this.filters = { ...this.filters, ...partial }
    },
  },
})
