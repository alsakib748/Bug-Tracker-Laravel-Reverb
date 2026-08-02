import { defineStore } from 'pinia'
import api from '@/services/api'

export const useProjectStore = defineStore('projects', {
  state: () => ({
    projects: [],
    project: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),
  actions: {
    async fetchAllProjects(params = {}) {
      this.loading = true

      try {
        const response = await api.get('/api/projects', { params })
        // console.log('API response: ', response.data)
        this.projects = response.data.data
        this.pagination.total = this.projects.length
        return response.data
      } catch (error) {
        console.error('Failed to fetch projects: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchProjects(params = {}) {
      this.loading = true

      try {
        const response = await api.get('/api/projects', { params })
        this.projects = response.data.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch projects: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createProject(data) {
      this.loading = true
      try {
        const response = await api.post('/api/projects', data)
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
        const response = await api.get(`/api/projects/${id}`)
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
        const response = await api.put(`/api/projects/${id}`, data)
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
        await api.delete(`/api/projects/${id}`)
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
      const response = await api.get(`/api/projects/${projectId}/members`)
      return response.data.data
    },
    // * Fetch available users
    async fetchAvailableUsers(projectId) {
      const response = await api.get(`/api/projects/${projectId}/members/available`)
      return response.data.data
    },
    // * Add a member
    async addMember(projectId, userId, role) {
      const response = await api.post(`/api/projects/${projectId}/members`, {
        user_id: userId,
        role: role,
      })
      return response.data
    },
    // * Remove a member
    async removeMember(projectId, userId) {
      const response = await api.delete(`/api/projects/${projectId}/members/${userId}`)
      return response.data
    },
  },
})
