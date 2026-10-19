<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="pageTitle"
      description="Review this notification and jump to the related page."
    />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Notification Details">
        <AppSkeleton v-if="loading" :rows="6" />
        <div v-else-if="notification" class="space-y-4">
          <div class="flex items-center gap-2">
            <AppBadge :color="notification.read_at ? 'success' : 'info'">
              {{ notification.read_at ? '✅ Read' : '🔵 Unread' }}
            </AppBadge>
            <span class="text-sm text-ink-muted dark:text-gray-400">
              {{ formatDate(notification.created_at) }}
            </span>
          </div>
          <div class="rounded-card border border-line bg-surface p-4">
            <h3 class="text-lg font-semibold text-ink dark:text-gray-300">
              {{ getTitle(notification) }}
            </h3>
            <p class="mt-2 text-ink dark:text-gray-300">{{ getMessage(notification) }}</p>
          </div>
          <div v-if="notification.data?.url">
            <router-link :to="notification.data.url" class="text-blue-600 hover:underline">
              Go to related page
            </router-link>
          </div>
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const notification = ref(null)
const loading = ref(false)
const pageTitle = ref('Notification Details')

const loadNotification = async () => {
  loading.value = true
  try {
    // Since we don't have a single notification endpoint,
    // we fetch all and find by id (or implement a dedicated endpoint)
    await notificationStore.fetchNotifications(1)
    const found = notificationStore.notifications.find((n) => n.id === route.params.id)
    if (found) {
      notification.value = found
      // Mark as read automatically
      await notificationStore.markAsRead(found.id)
    } else {
      router.push('/notifications')
    }
  } catch (error) {
    router.push('/notifications')
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  const d = new Date(dateString)
  return d.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const getTitle = (notify) => {
  const type = notify.type
  const data = notify.data
  switch (type) {
    case 'issue_assigned':
      return 'Issue Assigned'
    case 'comment_added':
      return 'New Comment'
    case 'status_changed':
      return 'Status Changed'
    case 'project_member_added':
      return 'Project Member Added'
    default:
      return 'Notification'
  }
}

const getMessage = (notify) => {
  // Reuse the same logic from NotificationItem
  // or just return the raw data message.
  return notify.data?.message || 'Notification'
}

onMounted(loadNotification)
</script>
