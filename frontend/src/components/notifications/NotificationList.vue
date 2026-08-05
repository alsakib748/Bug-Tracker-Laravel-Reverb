<template>
  <div class="space-y-2">
    <NotificationItem v-for="notify in notifications" :key="notify.id" :notification="notify"
      @click="handleClick(notify)" />
    <div v-if="loading" class="text-center py-4">
      <i class="pi pi-spin pi-spinner text-blue-600"></i>
    </div>
    <div v-else-if="notifications.length === 0" class="text-center py-8 text-gray-500">
      No notifications.
    </div>
    <div v-if="hasMorePages" class="flex justify-center mt-4">
      <button @click="loadMore" class="text-blue-600 hover:underline text-sm" :disabled="loadingMore">
        {{ loadingMore ? 'Loading...' : 'Load more' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/stores/notifications';
import NotificationItem from './NotificationItem.vue';
import Paginator from 'primevue/paginator';

const props = defineProps({
  limit: {
    type: Number,
    default: null, // if null, show all (full page)
  },
});

const emit = defineEmits(['navigate']);

const notificationStore = useNotificationStore();

const { notifications, pagination, loading } = storeToRefs(notificationStore);

const loadingMore = ref(false);

const displayNotifications = computed(() => {
  if (props.limit) {
    return notifications.value.slice(0, props.limit);
  }
  return notifications.value;
});

const hasMorePages = computed(() => {
  if (props.limit) return false; // in dropdown, no load more
  return pagination.value.current_page < pagination.value.last_page;
});

const handleClick = async (notify) => {
  if (!notify.read_at) {
    await notificationStore.markAsRead(notify.id);
  }
  // Navigate using the URL if present
  if (notify.data.url) {
    // Use router or window location
    emit('navigate', notify.data.url);
  }
};

const loadMore = async () => {
  if (loadingMore.value || !hasMorePages.value) return;
  loadingMore.value = true;
  try {
    await notificationStore.fetchNotifications(pagination.value.current_page + 1);
  } finally {
    loadingMore.value = false;
  }
};

// const onPageChange = (event) => {
//   notificationStore.fetchNotifications(event.page + 1);
// };
</script>
