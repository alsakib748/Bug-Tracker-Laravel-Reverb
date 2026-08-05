<template>
  <div class="relative">
    <NotificationBadge @click="toggleDropdown" />
    <!-- <div v-if="isOpen"
      class="absolute right-0 mt-2 w-100 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
      <div class="p-3 border-b border-gray-200 flex justify-between items-center">
        <h4 class="font-semibold">Notifications</h4>
        <button v-if="unreadCount > 0" @click="markAllRead" class="text-sm text-blue-600 hover:underline">
          Mark all as read
        </button>
      </div>
      <div v-if="loading" class="text-center py-4">
        <i class="pi pi-spin pi-spinner text-blue-600"></i>
      </div>
      <div v-else-if="notifications.length === 0" class="text-center py-4 text-gray-500">
        No notifications
      </div>
      <div v-else>
        <NotificationItem v-for="notify in notifications.slice(0, 5)" :key="notify.id" :notification="notify"
          @click="handleClick(notify)" />
        <div class="p-2 text-center border-t border-gray-200">
          <router-link to="/notifications" class="text-sm text-blue-600 hover:underline">
            View all
          </router-link>
        </div>
      </div>
    </div> -->

    <div v-if="isOpen"
      class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
      <div class="p-3 border-b border-gray-200 flex justify-between items-center">
        <h4 class="font-semibold">Notifications</h4>
        <button v-if="unreadCount > 0" @click="markAllRead" class="text-sm text-blue-600 hover:underline">
          Mark all as read
        </button>
      </div>
      <NotificationList :limit="5" @navigate="handleNavigate" />
      <div class="p-2 text-center border-t border-gray-200">
        <router-link to="/notifications" class="text-sm text-blue-600 hover:underline">
          View all notifications
        </router-link>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notifications';
import NotificationBadge from './NotificationBadge.vue';
// import NotificationItem from './NotificationItem.vue';
import NotificationList from './NotificationList.vue';

const router = useRouter();
const notificationStore = useNotificationStore();

// const loading = ref(false);

// const notifications = computed(() => notificationStore.notifications);
// const unreadCount = computed(() => notificationStore.unreadCount);
const { unreadCount } = storeToRefs(notificationStore);

const isOpen = ref(false);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    // 🔥 Always fetch latest notifications when dropdown opens
    notificationStore.fetchNotifications(1);
  }
};

// const loadNotifications = async () => {
//   loading.value = true;
//   try {
//     await notificationStore.fetchNotifications(1);
//   } catch (error) {
//     console.error('Error loading notifications:', error);
//   } finally {
//     loading.value = false;
//   }
// };

const markAllRead = async () => {
  await notificationStore.markAllAsRead();
};

const handleNavigate = (url) => {
  isOpen.value = false;
  router.push(url);
};

// const handleClick = async (notify) => {
//   if (!notify.read_at) {
//     await notificationStore.markAsRead(notify.id);
//   }
//   // Navigate if URL exists
//   if (notify.data.url) {
//     // You can use router.push or window.location
//     // We'll emit a close and let parent handle navigation
//     isOpen.value = false;
//     // For now, just close dropdown
//   }
// };

// Close dropdown on outside click (optional)
</script>
