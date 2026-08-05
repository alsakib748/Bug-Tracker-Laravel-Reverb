<template>
  <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
    :class="{ 'bg-blue-50': !notification.read_at }" @click="$emit('click', notification)">
    <div class="flex justify-between items-start">
      <div class="flex-1">
        <p class="text-sm text-gray-800">{{ getMessage(notification) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ timeAgo(notification.created_at) }}</p>
      </div>
      <div v-if="!notification.read_at" class="ml-2">
        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
});

defineEmits(['click']);

const getMessage = (notify) => {
  const type = notify.type;
  const data = notify.data;
  switch (type) {
    case 'issue_assigned':
      return `Issue "${data.title}" was assigned to you by ${data.assigned_by}.`;
    case 'issue_created':
      return `New issue "${data.title}" was created by ${data.created_by}.`;
    case 'comment_added':
      return `${data.comment_author} commented on "${data.title}": ${data.comment_snippet}`;
    case 'status_changed':
      return `${data.changed_by} changed status of "${data.title}" from ${data.old_status} to ${data.new_status}.`;
    case 'project_member_added':
      return `${data.added_by} added you to project "${data.project_name}".`;
    default:
      return 'New notification';
  }
};

const timeAgo = (dateString) => {
  const now = new Date();
  const date = new Date(dateString);
  const diff = Math.floor((now - date) / 1000);
  if (diff < 60) return `${diff} seconds ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
  return `${Math.floor(diff / 86400)} days ago`;
};
</script>
