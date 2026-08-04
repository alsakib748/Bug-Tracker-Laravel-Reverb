import { defineStore } from 'pinia'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: {
      total_projects: 0,
      total_issues: 0,
      open_issues: 0,
      in_progress_issues: 0,
      closed_issues: 0,
      critical_issues: 0,
      recent_activity: [],
    },
    loading: false,
  }),
  actions: {
    async fetchDashboard() {
      this.loading = true
      try {
        const response = await api.get('/api/dashboard')
        console.log(response.data)
        this.stats = response.data
      } catch (error) {
        console.error('Failed to fetch dashboard', error)
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
