<template>
  <div class="space-y-2">
    <div v-if="loading" class="text-center py-4">
      <i class="pi pi-spin pi-spinner text-blue-600"></i>
    </div>
    <div v-else-if="attachments.length === 0" class="text-gray-500 text-center py-4">
      No attachments.
    </div>
    <div v-else>
      <AttachmentItem v-for="att in attachments" :key="att.id" :attachment="att" @delete="$emit('delete', $event)" />
    </div>
  </div>
</template>

<script setup>
import AttachmentItem from './AttachmentItem.vue';

defineProps({
  attachments: { type: Array, required: true },
  loading: { type: Boolean, default: false },
});

defineEmits(['delete']);
</script>
