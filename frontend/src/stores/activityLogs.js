import { defineStore } from 'pinia'
import api from '@/services/api'

export const useActivityLogStore = defineStore('activityLogs', {
  state: () => ({
    logs: [],
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    },
  }),
  actions: {
    async fetchLogs(filters = {}, page = 1) {
      this.loading = true
      try {
        const response = await api.get('/api/activity-logs', {
          params: { ...filters, page },
        })
        this.logs = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        }
        return response.data
      } catch (error) {
        console.error('Failed to fetch activity logs:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
