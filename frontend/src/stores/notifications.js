import { defineStore } from 'pinia'
import api from '@/services/api'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
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
        const response = await api.get('/api/notifications/', { params: { page } })
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
        //  update unread count
        this.unreadCount = this.notifications.filter((n) => !n.read_at).length
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
        await api.patch(`/api/notifications/${id}/read`)
        const notify = this.notifications.find((n) => n.id === id)
        if (notify) {
          notify.read_at = new Date().toISOString()
          this.unreadCount = this.notifications.filter((n) => !n.read_at).length
        }
      } catch (error) {
        console.error('Failed to mark as read: ', error)
      }
    },
    async markAllAsRead() {
      try {
        await api.patch('/api/notifications/read-all')
        this.notifications.forEach((n) => (n.read_at = new Date().toISOString()))
        this.unreadCount = 0
      } catch (error) {
        console.error('Failed to mark all as read:', error)
      }
    },
    async deleteNotification(id) {
      try {
        await api.delete(`/api/notifications/${id}`)
        this.notifications = this.notifications.filter((n) => n.id !== id)
        this.unreadCount = this.notifications.filter((n) => !n.read_at).length
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    },
    // Called when a real-time notification arrives (later)
    addNotification(notification) {
      this.notifications.unshift(notification)
      if (!notification.read_at) this.unreadCount++
    },
  },
})
