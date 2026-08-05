<template>
  <div class="space-y-1">
    <div v-if="loading" class="text-center py-4">
      <i class="pi pi-spin pi-spinner text-blue-600"></i>
    </div>
    <div v-else-if="logs.length === 0" class="text-center py-8 text-gray-500">
      No activity recorded.
    </div>
    <div v-else>
      <ActivityItem v-for="log in logs" :key="log.id" :log="log" />
    </div>
  </div>
</template>

<script setup>
import ActivityItem from './ActivityItem.vue';

defineProps({
  logs: { type: Array, required: true },
  loading: { type: Boolean, default: false },
});
</script>
