import { defineStore } from 'pinia'
import api from '@/services/api'

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    user: null,
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
    async fetchUsers(params = {}, { append = false } = {}) {
      if (append) this.loadingMore = true
      else this.loading = true
      try {
        const response = await api.get('/users', { params })
        const items = response.data.data || []
        // ResourceCollection + paginator nests paging under `meta`
        const meta = response.data.meta || response.data
        if (append) {
          const seen = new Set(this.users.map((user) => user.id))
          this.users = [...this.users, ...items.filter((user) => !seen.has(user.id))]
        } else {
          this.users = items
        }
        this.pagination = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 15,
          total: meta.total || 0,
        }
        return response.data
      } catch (error) {
        console.error('Failed to fetch users:', error)
        throw error
      } finally {
        if (append) this.loadingMore = false
        else this.loading = false
      }
    },
    removeUserFromList(id) {
      this.users = this.users.filter((user) => user.id !== id)
      this.pagination.total = Math.max(0, this.pagination.total - 1)
    },
    /**
     * Minimal, permission-light list of users for filtering activity logs.
     * Backed by /activity-logs/users, which authorizes on "view activity logs"
     * rather than the admin-only "view users" permission.
     */
    async fetchActivityFilterUsers() {
      try {
        const response = await api.get('/activity-logs/users')
        return response.data.data || []
      } catch (error) {
        console.error('Failed to fetch activity filter users:', error)
        throw error
      }
    },
    async fetchUser(id) {
      this.loading = true
      try {
        // const response = await api.get(`/api/users/${id}`)
        const response = await api.get(`/users/${id}`)
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
        // const response = await api.post('/api/users', data)
        const response = await api.post('/users', data)
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
        // const response = await api.put(`/api/users/${id}`, data)
        const response = await api.put(`/users/${id}`, data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update user:', error)
        // const message = extractErrorMessage(error)
        // toast.error(message) // single-line message
        // const message = error.response?.data?.message || 'An error occurred.'
        // toast.error(message)
        throw error
      } finally {
        this.loading = false
      }
    },
    async deleteUser(id) {
      try {
        // await api.delete(`/api/users/${id}`)
        await api.delete(`/users/${id}`)
      } catch (error) {
        console.error('Failed to delete user:', error)
        throw error
      }
    },
    // Profile actions
    async updateProfile(data) {
      try {
        // const response = await api.post('/api/user/profile', data)
        const response = await api.post('/user/profile', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update profile:', error)
        throw error
      }
    },
    async fetchProfile() {
      try {
        // const response = await api.get('/api/user/profile')
        const response = await api.get('/user/profile')
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch profile:', error)
        throw error
      }
    },
  },
})
