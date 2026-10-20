import { defineStore } from 'pinia'
import api from '@/services/api'

// Concurrent identical list loads (e.g. DashboardFilterBar + DashboardPage
// both bootstrapping the same GET /projects) share one in-flight promise
// instead of issuing duplicate requests.
let listInFlight = null
let listInFlightKey = null

export const useProjectStore = defineStore('projects', {
  state: () => ({
    projects: [],
    project: null,
    loading: false,
    loadingMore: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),
  actions: {
    async fetchAllProjects(params = {}, { append = false } = {}) {
      if (append) return this.fetchProjectsPage(params, true)

      const key = JSON.stringify(params)
      if (listInFlight && listInFlightKey === key) return listInFlight

      listInFlightKey = key
      listInFlight = this.fetchProjectsPage(params, false).finally(() => {
        listInFlight = null
        listInFlightKey = null
      })

      return listInFlight
    },
    async fetchProjectsPage(params = {}, append = false) {
      if (append) this.loadingMore = true
      else this.loading = true

      try {
        const response = await api.get('/projects', { params })
        const items = response.data.data || []
        // ResourceCollection + paginator nests paging under `meta`
        const meta = response.data.meta || response.data
        if (append) {
          const seen = new Set(this.projects.map((project) => project.id))
          this.projects = [...this.projects, ...items.filter((project) => !seen.has(project.id))]
        } else {
          this.projects = items
        }
        this.pagination = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 15,
          total: meta.total || 0,
        }
        return response.data
      } catch (error) {
        console.error('Failed to fetch projects: ', error)
        throw error
      } finally {
        if (append) this.loadingMore = false
        else this.loading = false
      }
    },
    removeProjectFromList(id) {
      this.projects = this.projects.filter((project) => project.id !== id)
      this.pagination.total = Math.max(0, this.pagination.total - 1)
    },
    async createProject(data) {
      this.loading = true
      try {
        // const response = await api.post('/api/projects', data)
        const response = await api.post('/projects', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to create project: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchProject(id) {
      this.loading = true
      try {
        // const response = await api.get(`/api/projects/${id}`)
        const response = await api.get(`/projects/${id}`)
        this.project = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch project: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateProject(id, data) {
      this.loading = true
      try {
        // const response = await api.put(`/api/projects/${id}`, data)
        const response = await api.put(`/projects/${id}`, data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update project: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async deleteProject(id) {
      this.loading = true
      try {
        // await api.delete(`/api/projects/${id}`)
        await api.delete(`/projects/${id}`)
        this.projects = this.projects.filter((p) => p.id !== id)
      } catch (error) {
        console.error('Failed to delete project: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // * Fetch members of a project
    async fetchMembers(projectId) {
      const response = await api.get(`/projects/${projectId}/members`)
      return response.data.data
    },
    // * Fetch available users
    async fetchAvailableUsers(projectId) {
      // const response = await api.get(`/api/projects/${projectId}/members/available`)
      const response = await api.get(`/projects/${projectId}/members/available`)
      return response.data.data
    },
    // * Add a member
    async addMember(projectId, userId, role) {
      // const response = await api.post(`/api/projects/${projectId}/members`, {
      //   user_id: userId,
      //   role: role,
      // })
      const response = await api.post(`/projects/${projectId}/members`, {
        user_id: userId,
        role: role,
      })
      return response.data
    },
    // * Remove a member
    async removeMember(projectId, userId) {
      // const response = await api.delete(`/api/projects/${projectId}/members/${userId}`)
      const response = await api.delete(`/projects/${projectId}/members/${userId}`)
      return response.data
    },
  },
})
