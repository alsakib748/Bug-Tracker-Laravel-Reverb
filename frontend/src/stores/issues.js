import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'
import { useAuthStore } from './auth'

export const useIssueStore = defineStore('issues', {
  state: () => ({
    issues: [],
    issue: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),
  actions: {
    async fetchIssues(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/issues', { params })
        console.log('API response:', response.data)
        this.issues = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0,
        }
        console.log('Store issues after assignment: ', this.issues)
        return response.data
      } catch (error) {
        console.error('Failed to fetch issues: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchIssue(id) {
      this.loading = true
      try {
        const response = await api.get(`/api/issues/${id}`)
        this.issue = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createIssue(data) {
      this.loading = true
      try {
        const response = await api.post('/api/issues', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to create issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateIssue(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/api/issues/${id}`, data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update issue: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async deleteIssue(id) {
      this.loading = true
      try {
        await api.delete(`/api/issues/${id}`)
      } catch (error) {
        console.error('Failed to delete issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    // Business actions
    async assignIssue(id, userId) {
      const response = await api.patch(`/api/issues/${id}/assign`, { user_id: userId })
      return response.data.data
    },
    async changeStatus(id, status) {
      const response = await api.patch(`/api/issues/${id}/status`, { status })
      return response.data.data
    },
    async reopenIssue(id) {
      const response = await api.patch(`/api/issues/${id}/reopen`)
      return response.data.data
    },
    async closeIssue(id) {
      const response = await api.patch(`/api/issues/${id}/close`)
      return response.data.data
    },

    initializeListeners() {
      const auth = useAuthStore()
      if (!auth.authenticated) return

      // Listen for user-specific events (issue assigned)
      echo.private(`private-user.${auth.user.id}`).listen('issue.assigned', (data) => {
        // Update local issue list or fetch fresh ?
        // For simplicity, update the relevant issue in th list
        const index = this.issues.findIndex((i) => i.id === data.id)
        if (index !== -1) {
          // Update the issue's assignee if present
          this.issues[index].assigned_to = data.assigned_to
        }
        // Or you can fetch list: this.fetchIssues();
      })
    },
  },
})
