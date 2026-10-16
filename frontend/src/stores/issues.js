import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'

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
        // const response = await api.get('/api/issues', { params })
        const response = await api.get('/issues', { params })
        // console.log('API response:', response.data)
        this.issues = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0,
        }
        // console.log('Store issues after assignment: ', this.issues)
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
        // const response = await api.get(`/api/issues/${id}`)
        const response = await api.get(`/issues/${id}`)
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
        // const response = await api.post('/api/issues', data)
        const response = await api.post('/issues', data)
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
        // const response = await api.put(`/api/issues/${id}`, data)
        const response = await api.put(`/issues/${id}`, data)
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
        // await api.delete(`/api/issues/${id}`)
        await api.delete(`/issues/${id}`)
      } catch (error) {
        console.error('Failed to delete issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    // Business actions
    async assignIssue(id, userId) {
      // const response = await api.patch(`/api/issues/${id}/assign`, { user_id: userId })
      const response = await api.patch(`/issues/${id}/assign`, { user_id: userId })
      return response.data.data
    },
    async changeStatus(id, status) {
      // const response = await api.patch(`/api/issues/${id}/status`, { status })
      const response = await api.patch(`/issues/${id}/status`, { status })
      return response.data.data
    },
    async reopenIssue(id) {
      // const response = await api.patch(`/api/issues/${id}/reopen`)
      const response = await api.patch(`/issues/${id}/reopen`)
      return response.data.data
    },
    async closeIssue(id) {
      // const response = await api.patch(`/api/issues/${id}/close`)
      const response = await api.patch(`/issues/${id}/close`)
      return response.data.data
    },
    // listenToIssue(issueId) {
    //   if (!issueId) return

    //   console.log(`Listening to issue.${issueId}`)

    //   echo
    //     .private(`issue.${issueId}`)

    //     .listen('.issue.status.changed', (event) => {
    //       console.log('REAL-TIME ISSUE STATUS:', event)

    //       if (this.issue && this.issue.id === event.issue_id) {
    //         this.issue = {
    //           ...this.issue,

    //           status: event.new_status,

    //           status_label: event.status_label ?? this.issue.status_label,
    //         }
    //       }
    //     })

    //     .listen('.issue.assigned', (event) => {
    //       console.log('REAL-TIME ISSUE ASSIGNED:', event)

    //       if (this.issue && this.issue.id === event.issue_id) {
    //         this.issue = {
    //           ...this.issue,

    //           assigned_to: event.assigned_to,

    //           assignee: event.assignee,
    //         }
    //       }
    //     })

    //     .listen('.issue.updated', (event) => {
    //       console.log('REAL-TIME ISSUE UPDATED:', event)

    //       if (this.issue && this.issue.id === event.issue.id) {
    //         this.issue = event.issue
    //       }
    //     })
    // },

    stopListeningToIssue(issueId) {
      if (!issueId) return

      echo.leave(`issue.${issueId}`)
    },

    // listenToProject(projectId) {
    //   if (!projectId) return

    //   echo.private(`project.${projectId}`).listen('.issue.created', (event) => {
    //     const newIssue = event.issue

    //     const exists = this.issues.some((issue) => issue.id === newIssue.id)

    //     if (!exists) {
    //       this.issues.unshift(newIssue)
    //     }
    //   })
    // },
    // stopListening(projectId) {
    //   echo.leave(`project.${projectId}`)
    // },

    // initializeListeners(issueId) {
    //   if (!issueId) return

    //   console.log(`Subscribing to issue.${issueId} for comments`)

    //   echo.private(`issue.${issueId}`).listen('.comment.created', (event) => {
    //     console.log('Comment created:', event)

    //     const comment = event.comment

    //     if (!comment) return

    //     const exists = this.comments.some((item) => item.id === comment.id)

    //     if (!exists) {
    //       this.comments.push(comment)
    //     }
    //   })
    // },
    stopListeners(issueId) {
      if (!issueId) return

      echo.leave(`issue.${issueId}`)
    },
  },
})
