<template>
  <div class="bg-white rounded-lg shadow p-4 border border-gray-100 mb-3">
    <div class="flex items-start justify-between">
      <div class="flex items-center">
        <img
          :src="comment.user_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.user_name || 'User')}&background=random&size=32`"
          class="w-8 h-8 rounded-full mr-3" :alt="comment.user_name" />
        <div>
          <span class="font-medium text-gray-800">{{ comment.user_name || 'Unknown User' }}</span>
          <span class="text-xs text-gray-500 ml-2">
            {{ formatDate(comment.created_at) }}
          </span>
          <span v-if="isEdited" class="text-xs text-gray-400 ml-2">(edited)</span>
        </div>
      </div>
      <div class="flex gap-2" v-if="canEdit || canDelete">
        <button v-if="canEdit" @click="$emit('edit', comment)" class="text-blue-600 hover:text-blue-800 text-sm">
          Edit
        </button>
        <button v-if="canDelete" @click="confirmDelete(comment.id)" class="text-red-600 hover:text-red-800 text-sm">
          Delete
        </button>
      </div>
    </div>
    <div class="mt-2 text-gray-700 whitespace-pre-wrap">
      {{ comment.comment }}
    </div>
  </div>
</template>

<script setup>

import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  comment: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  }
});


const emit = defineEmits(['edit', 'delete']);

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-us', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const isEdited = computed(() => {
  return props.comment.created_at !== props.comment.updated_at;
});

const confirmDelete = (commentId) => {
  Swal.fire({
    title: 'Delete Comment?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete',
  }).then((result) => {
    if (result.isConfirmed) {
      emit('delete', commentId);
    }
  });
}

</script>
