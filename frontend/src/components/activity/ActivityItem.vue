<template>
  <div class="flex items-start space-x-3 py-3 border-b border-gray-100">
    <img
      :src="log.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(log.user?.name || 'User')}&background=random&size=32`"
      class="w-8 h-8 rounded-full flex-shrink-0" />
    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-800">
          <span class="font-medium">{{ log.user?.name || 'System' }}</span>
          <span class="text-gray-600">{{ log.description }}</span>
        </p>
        <span class="text-xs text-gray-400 whitespace-nowrap ml-2">
          {{ timeAgo(log.created_at) }}
        </span>
      </div>
      <div class="text-xs text-gray-500 mt-1">
        <span v-if="log.project">Project: {{ log.project.name }}</span>
        <span v-if="log.issue" class="ml-2">Issue: #{{ log.issue.id }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  log: { type: Object, required: true },
});

const timeAgo = (dateString) => {
  const now = new Date();
  const date = new Date(dateString);
  const diff = Math.floor((now - date) / 1000);
  if (diff < 60) return `${diff}s`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
  return `${Math.floor(diff / 86400)}d`;
};
</script>
