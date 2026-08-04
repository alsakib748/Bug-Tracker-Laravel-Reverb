<template>
  <div class="space-y-4">
    <div v-if="loading" class="text-center py-4">
      <i class="pi pi-spin pi-spinner text-xl text-blue-600"></i>
    </div>
    <div v-else-if="comments.length === 0" class="text-gray-500 text-center py-4">
      No comments yet. Be the first to comment!
    </div>
    <div v-else>
      <CommentItem v-for="comment in comments" :key="comment.id" :comment="comment" :canEdit="canEdit(comment)"
        :canDelete="canDelete(comment)" @edit="startEdit" @delete="handleDelete" />
    </div>
  </div>
</template>

<script setup>

import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import CommentItem from './CommentItem.vue';

const props = defineProps({
  comments: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['edit', 'delete']);

const authStore = useAuthStore();

const canEdit = (comment) => {
  return authStore.isAdmin || authStore.user?.id === comment.user_id;
};

const canDelete = (comment) => {
  return authStore.isAdmin || authStore.user?.id === comment.user_id;
}

const startEdit = (comment) => {
  emit('edit', comment);
}

const handleDelete = (commentId) => {
  emit('delete', commentId);
}

</script>
