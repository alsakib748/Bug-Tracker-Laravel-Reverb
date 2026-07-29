import { defineStore } from 'pinia'
import api, { getCsrfToken, resetCsrfToken } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    authenticated: false,
    loading: false,
  }),
  actions: {
    async login(credentials) {
      this.loading = true
      try {
        // 1. Get CSRF cookie (cached after first call)
        await getCsrfToken()

        // 2. Login
        const result = await api.post('/api/login', credentials)

        if (result) {
          this.authenticated = true

          // 3. Fetch user
          await this.getUser()

          return true
        }
      } catch (error) {
        console.error('Login failed: ', error)
        return false
      } finally {
        this.loading = false
      }
    },
    async getUser() {
      try {
        const response = await api.get('/api/user')
        const data = response.data
        this.user = data
        console.log(this.user.name)
        return true
      } catch (error) {
        console.error('User profile failed: ', error)
        return false
      }
    },
    async logout() {
      try {
        await getCsrfToken()
        await api.post('/api/logout')

        // Clear auth state
        this.user = null
        this.authenticated = false

        // Reset CSRF token so next login gets fresh token
        resetCsrfToken()

        return true
      } catch (error) {
        console.error('Logout failed: ', error)
        return false
      }
    },
  },
})
