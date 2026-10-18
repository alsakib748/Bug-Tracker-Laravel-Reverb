import { defineStore } from 'pinia'
import api, { getCsrfToken, resetCsrfToken } from '@/services/api'
import { useNotificationStore } from './notifications'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    authenticated: false,
    loading: false,
    roles: [],
    permissions: [],
  }),
  getters: {
    isAdmin: (state) =>
      Boolean(state.user?.is_admin) || state.roles.includes('Admin'),
    isDeveloper: (state) => state.roles.includes('Developer'),
    isTester: (state) => state.roles.includes('QA Tester'),
    hasRole: (state) => (role) => state.roles.includes(role),
    hasPermission: (state) => (permission) =>
      Boolean(state.user?.is_admin) || state.permissions.includes(permission),
    hasAnyPermission: (state) => (list = []) =>
      Boolean(state.user?.is_admin) ||
      list.some((permission) => state.permissions.includes(permission)),
    hasAllPermissions: (state) => (list = []) =>
      Boolean(state.user?.is_admin) ||
      list.every((permission) => state.permissions.includes(permission)),
  },
  actions: {
    async login(credentials) {
      this.loading = true

      try {
        // 1. Get CSRF cookie (cached after first call)
        await getCsrfToken()

        // 2. Login
        // const result = await api.post('/api/login', credentials)
        await api.post('/login', credentials)

        this.authenticated = true

        // 3. Fetch user
        await this.getUser()

        return true
      } catch (error) {
        console.error('Login failed: ', error)
        return false
      } finally {
        this.loading = false
      }
    },
    async register(payload) {
      this.loading = true

      try {
        // 1. Get CSRF cookie (cached after first call)
        await getCsrfToken()

        // 2. Register
        await api.post('/register', payload)

        this.authenticated = true

        // 3. Fetch user
        await this.getUser()

        return true
      } catch (error) {
        console.error('Registration failed: ', error)
        return false
      } finally {
        this.loading = false
      }
    },
    async getUser() {
      try {
        // const response = await api.get('/api/user')
        const response = await api.get('/user')
        // Normalize common response shapes so `user` is always the user object
        // Support: { data: user }, { user: user }, or direct user object
        this.user = response.data?.data ?? response.data?.user ?? response.data
        this.roles = this.user?.roles ?? []
        this.permissions = this.user?.permissions ?? []
        this.authenticated = true
        // console.log(this.user.name)
        return true
      } catch (error) {
        this.user = null
        this.authenticated = false
        throw error
        // console.error('User profile failed: ', error)
        // return false
      }
    },
    async checkAuth() {
      try {
        await this.getUser()
      } catch {}
    },
    async fetchPermissions() {
      try {
        const response = await api.get('/me/permissions')
        this.roles = response.data?.roles ?? []
        this.permissions = response.data?.permissions ?? []
        return response.data
      } catch (error) {
        this.roles = []
        this.permissions = []
        throw error
      }
    },
    // async checkAuth() {
    //   try {
    //     const response = await api.get('/api/user')
    //     if (response.status === 200) {
    //       this.user = response.data
    //       this.authenticated = true
    //       return true
    //     }
    //     return false
    //   } catch (error) {
    //     console.error('Auth error: ', error)
    //     this.user = null
    //     this.authenticated = false
    //     return false
    //   }
    // },
    async logout() {
      try {
        // await getCsrfToken()
        // await api.post('/api/logout')
        await api.post('/logout')

        // Reset notification listeners so the next login can re-register
        try {
          const notificationStore = useNotificationStore()
          notificationStore.stopListeners()
          notificationStore.listenersInitialized = false
        } catch (_) {}

        // Clear auth state
        this.user = null
        this.roles = []
        this.permissions = []
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
