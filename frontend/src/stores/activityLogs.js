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
        // const response = await api.get('/api/activity-logs', {
        //   params: { ...filters, page },
        // })
        const response = await api.get('/activity-logs', {
          params: { ...filters, page },
        })
        this.logs = response.data.data
        // ResourceCollection + paginator nests paging under `meta`
        const meta = response.data.meta || response.data
        this.pagination = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 20,
          total: meta.total || 0,
        }
        return response.data
      } catch (error) {
        console.error('Failed to fetch activity logs:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchLog(id) {
      try {
        const response = await api.get(`/activity-logs/${id}`)
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch activity log:', error)
        throw error
      }
    },
  },
})
