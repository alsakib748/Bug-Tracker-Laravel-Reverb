import { defineStore } from 'pinia'
import api from '@/services/api'

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    user: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),
  actions: {
    async fetchUsers(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/users', { params })
        this.users = [...(response.data.data || [])]
        this.pagination = {
          current_page: response.data.meta?.current_page || 1,
          last_page: response.data.meta?.last_page || 1,
          per_page: response.data.meta?.per_page || 15,
          total: response.data.meta?.total || 0,
        }
        return response.data
      } catch (error) {
        console.error('Failed to fetch users:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchUser(id) {
      this.loading = true
      try {
        const response = await api.get(`/api/users/${id}`)
        this.user = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch user:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createUser(data) {
      this.loading = true
      try {
        const response = await api.post('/api/users', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to create user:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateUser(id, data) {
      this.loading = true
      try {
        const response = await api.put(`/api/users/${id}`, data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update user:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async deleteUser(id) {
      try {
        await api.delete(`/api/users/${id}`)
      } catch (error) {
        console.error('Failed to delete user:', error)
        throw error
      }
    },
    // Profile actions
    async updateProfile(data) {
      try {
        const response = await api.post('/api/user/profile', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update profile:', error)
        throw error
      }
    },
    async fetchProfile() {
      try {
        const response = await api.get('/api/user/profile')
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch profile:', error)
        throw error
      }
    },
  },
})
