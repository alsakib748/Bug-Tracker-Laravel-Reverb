<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
      @click="toggleDropdown">
      <span v-if="unreadCount > 0" class="absolute right-0 top-0.5 z-1 flex h-2 w-2">
        <span class="absolute inline-flex w-full h-full bg-orange-400 rounded-full opacity-75 -z-1 animate-ping"></span>
        <span class="relative inline-flex w-2 h-2 bg-orange-500 rounded-full"></span>
      </span>
      <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
        xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd"
          d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
          fill="" />
      </svg>
      <span v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 px-1 flex items-center justify-center">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div v-if="dropdownOpen"
      class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0">
      <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
        <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Notifications ({{ unreadCount }} unread)
        </h5>
        <div class="flex items-center gap-2">
          <button v-if="unreadCount > 0" @click="markAllRead"
            class="text-xs text-blue-600 hover:underline dark:text-blue-400">
            Mark all read
          </button>
          <button @click="closeDropdown" class="text-gray-500 dark:text-gray-400">
            <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path fill-rule="evenodd" clip-rule="evenodd"
                d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                fill="currentColor" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex-1 flex items-center justify-center">
        <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
      </div>

      <!-- Empty State -->
      <div v-else-if="displayNotifications.length === 0" class="flex-1 flex items-center justify-center text-gray-500">
        No notifications yet
      </div>

      <!-- Notification List -->
      <ul v-else class="flex-1 overflow-y-auto custom-scrollbar -mx-3 px-3">
        <li v-for="notification in displayNotifications" :key="notification.id" @click="handleItemClick(notification)"
          class="cursor-pointer">
          <div
            class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
            :class="{
              'bg-blue-50 dark:bg-blue-900/20': !notification.read_at
            }">
            <!-- Avatar -->
            <span class="relative block w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
              <img :src="getUserAvatar(notification)" alt="User" class="w-full h-full object-cover" />
            </span>

            <span class="block flex-1 min-w-0">
              <span class="mb-1.5 block text-theme-sm text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-white/90">
                  {{ getNotificationUser(notification) }}
                </span>
                {{ getNotificationAction(notification) }}
                <span class="font-medium text-gray-800 dark:text-white/90">
                  {{ getNotificationTitle(notification) }}
                </span>
              </span>

              <span class="flex items-center gap-2 text-gray-500 text-theme-xs dark:text-gray-400">
                <span>{{ getNotificationType(notification) }}</span>
                <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                <span>{{ timeAgo(notification.created_at) }}</span>
                <span v-if="!notification.read_at" class="w-2 h-2 bg-blue-500 rounded-full"></span>
              </span>
            </span>
          </div>
        </li>
      </ul>

      <router-link to="/notifications"
        class="mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        @click="handleViewAllClick">
        View All Notifications
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink, useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'

const router = useRouter()
const notificationStore = useNotificationStore()
const { notifications, loading, unreadCount } = storeToRefs(notificationStore)

const dropdownOpen = ref(false)
const dropdownRef = ref(null)

const displayNotifications = computed(() => {
  return notifications.value.slice(0, 10) // Show latest 10 in dropdown
})

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
  if (dropdownOpen.value) {
    // Refresh notifications when opening
    notificationStore.fetchNotifications(1)
    // Initialize listeners if not already
    notificationStore.initializeListeners()
  }
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const markAllRead = async () => {
  await notificationStore.markAllAsRead()
}

const handleItemClick = async (notification) => {
  if (!notification.read_at) {
    await notificationStore.markAsRead(notification.id)
  }
  closeDropdown()
  // Navigate to the URL if it exists
  if (notification.data?.url) {
    router.push(notification.data.url)
  }
}

const handleViewAllClick = (event) => {
  event.preventDefault()
  closeDropdown()
  router.push('/notifications')
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

// Helper functions for display
const getUserAvatar = (notification) => {
  return notification.data?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(notification.data?.user_name || 'User')}&background=random&size=40`
}

const getNotificationUser = (notification) => {
  return notification.data?.user_name || notification.data?.assigned_by || 'System'
}

const getNotificationAction = (notification) => {
  const actions = {
    'issue_assigned': 'assigned issue',
    'issue_created': 'created issue',
    'comment_added': 'commented on issue',
    'status_changed': 'changed status of issue',
    'project_member_added': 'added you to project',
    'issue_closed': 'closed issue',
    'issue_reopened': 'reopened issue',
  }
  return actions[notification.type] || 'updated'
}

const getNotificationTitle = (notification) => {
  return notification.data?.title || notification.data?.project_name || ''
}

const getNotificationType = (notification) => {
  const types = {
    'issue_assigned': 'Assignment',
    'issue_created': 'New Issue',
    'comment_added': 'Comment',
    'status_changed': 'Status Update',
    'project_member_added': 'Project',
    'issue_closed': 'Closed',
    'issue_reopened': 'Reopened',
  }
  return types[notification.type] || notification.type
}

const timeAgo = (dateString) => {
  const diff = Math.floor((Date.now() - new Date(dateString)) / 1000)
  if (diff < 60) return `${diff}s ago`
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
