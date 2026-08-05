<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Notification Details">
        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <div v-else-if="notification" class="space-y-4">
          <div class="flex items-center gap-2">
            <span :class="{
              'text-green-600': notification.read_at,
              'text-blue-600': !notification.read_at,
            }">
              {{ notification.read_at ? '✅ Read' : '🔵 Unread' }}
            </span>
            <span class="text-sm text-gray-500">
              {{ formatDate(notification.created_at) }}
            </span>
          </div>
          <div class="p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold">{{ getTitle(notification) }}</h3>
            <p class="text-gray-700 mt-2">{{ getMessage(notification) }}</p>
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
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notifications';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();
const notification = ref(null);
const loading = ref(false);
const pageTitle = ref('Notification Details');

const loadNotification = async () => {
  loading.value = true;
  try {
    // Since we don't have a single notification endpoint,
    // we fetch all and find by id (or implement a dedicated endpoint)
    await notificationStore.fetchNotifications(1);
    const found = notificationStore.notifications.find(n => n.id === route.params.id);
    if (found) {
      notification.value = found;
      // Mark as read automatically
      await notificationStore.markAsRead(found.id);
    } else {
      router.push('/notifications');
    }
  } catch (error) {
    router.push('/notifications');
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const d = new Date(dateString);
  return d.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const getTitle = (notify) => {
  const type = notify.type;
  const data = notify.data;
  switch (type) {
    case 'issue_assigned': return 'Issue Assigned';
    case 'comment_added': return 'New Comment';
    case 'status_changed': return 'Status Changed';
    case 'project_member_added': return 'Project Member Added';
    default: return 'Notification';
  }
};

const getMessage = (notify) => {
  // Reuse the same logic from NotificationItem
  // or just return the raw data message.
  return notify.data?.message || 'Notification';
};

onMounted(loadNotification);
</script>
