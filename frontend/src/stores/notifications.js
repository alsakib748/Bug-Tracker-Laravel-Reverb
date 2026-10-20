import { defineStore } from 'pinia'
import api, { getCsrfToken } from '@/services/api'
import { onRealtimeReconnect } from '@/services/realtime'
import { useAuthStore } from './auth'

let removeReconnectListener = null
// Held at module scope (not in Pinia state) so it stays out of the store's
// serialisable snapshot. Echo is loaded lazily so the websocket stack
// (pusher-js + laravel-echo) is not part of the initial bundle.
let echoClient = null

const loadEcho = async () => {
  if (!echoClient) {
    const { default: echo } = await import('@/services/echo')
    echoClient = echo
  }

  return echoClient
}

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    loading: false,
    listenersInitialized: false, // prevent duplicate listeners
    fetchedForUserId: null, // bootstrap-once guard (AppHeader remounts per navigation)
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),
  getters: {
    unreadCount: (state) => state.notifications.filter((n) => !n.read_at).length,
  },
  actions: {
    async fetchNotifications(page = 1, append = false) {
      this.loading = true
      try {
        // const response = await api.get('/api/notifications/', { params: { page } })
        const response = await api.get('/notifications/', { params: { page } })
        if (append) {
          this.notifications = [...this.notifications, ...response.data.data]
        } else {
          this.notifications = response.data.data
        }

        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        }
        this.fetchedForUserId = useAuthStore().user?.id ?? null
        //  update unread count
        return response.data
      } catch (error) {
        console.error('Failed to fetch notifications: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async markAsRead(id) {
      try {
        // await api.patch(`/api/notifications/${id}/read`)
        await api.patch(`/notifications/${id}/read`)
        const notify = this.notifications.find((n) => n.id === id)
        if (notify) {
          notify.read_at = new Date().toISOString()
        }
      } catch (error) {
        console.error('Failed to mark as read: ', error)
      }
    },
    async markAllAsRead() {
      try {
        // await api.patch('/api/notifications/read-all')
        await api.patch('/notifications/read-all')
        this.notifications.forEach((n) => (n.read_at = new Date().toISOString()))
      } catch (error) {
        console.error('Failed to mark all as read:', error)
      }
    },
    async deleteNotification(id) {
      try {
        // await api.delete(`/api/notifications/${id}`)
        await api.delete(`/notifications/${id}`)
        this.notifications = this.notifications.filter((n) => n.id !== id)
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    },
    // Called when a real-time notification arrives (later)
    addNotification(notification) {
      this.notifications.unshift(notification)
    },
    async initializeListeners() {
      // Prevent duplicate listeners
      if (this.listenersInitialized) return

      const auth = useAuthStore()
      // Guard against partial auth state (e.g., user object exists but id not yet set,
      // which would subscribe to "user.undefined" and trigger 403 on broadcasting auth)
      if (!auth.authenticated || !auth.user?.id) {
        console.warn('Not authenticated, skipping notification listeners')
        return
      }

      // Ensure CSRF cookie is fresh before /api/broadcasting/auth
      try {
        await getCsrfToken()
      } catch (_) {}

      try {
        const echo = await loadEcho()
        echo.private(`user.${auth.user.id}`).listen('.notification.created', (data) => {
          // Backend broadcastsAs('notification.created') and wraps payload
          // in { notification: {...} }
          const notification = data?.notification ?? data
          this.notifications.unshift(notification)
        })

        removeReconnectListener = onRealtimeReconnect(() => {
          this.fetchNotifications().catch(() => {})
        })

        this.listenersInitialized = true
      } catch (error) {
        console.error('Failed to initialize notification listeners:', error)
      }
    },

    async stopListeners() {
      if (!this.listenersInitialized) return
      try {
        if (removeReconnectListener) {
          removeReconnectListener()
          removeReconnectListener = null
        }
        const auth = useAuthStore()
        if (auth.user?.id && echoClient) {
          echoClient.leave(`user.${auth.user.id}`)
        }
      } catch (error) {
        console.error('Failed to stop notification listeners:', error)
      } finally {
        this.listenersInitialized = false
      }
    },
  },
})
