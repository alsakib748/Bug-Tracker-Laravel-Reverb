<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Notifications">
        <div class="flex justify-between mb-4">
          <div class="text-sm text-gray-500">
            {{ unreadCount }} unread
          </div>
          <button v-if="unreadCount > 0" @click="markAllRead" class="text-blue-600 hover:underline text-sm">
            Mark all as read
          </button>
        </div>
        <NotificationList @navigate="handleNavigate" />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useNotificationStore } from '@/stores/notifications';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import NotificationList from '@/components/notifications/NotificationList.vue';

const pageTitle = ref('Notifications');
const notificationStore = useNotificationStore();
const router = useRouter();

const { unreadCount } = storeToRefs(notificationStore);
// const unreadCount = computed(() => notificationStore.unreadCount);

const loadNotifications = async () => {
  await notificationStore.fetchNotifications(1);
};

const markAllRead = async () => {
  await notificationStore.markAllAsRead();
};

const handleNavigate = (url) => {
  router.push(url);
};

onMounted(loadNotifications);
</script>
