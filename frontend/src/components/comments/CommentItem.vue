<template>
  <div class="rounded-card border border-line bg-surface p-4 shadow-theme-xs">
    <div class="flex items-start justify-between">
      <div class="flex items-center">
        <img
          :src="comment.user_avatar || '/images/avatar.jpg'"
          @error="onImgError"
          class="w-8 h-8 rounded-full mr-3"
          :alt="comment.user_name"
        />
        <div>
          <span class="font-medium text-gray-800 dark:text-gray-200">{{
            comment.user_name || 'Unknown User'
          }}</span>
          <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
            {{ formatDate(comment.created_at) }}
          </span>
          <span v-if="isEdited" class="text-xs text-gray-400 dark:text-gray-500 ml-2"
            >(edited)</span
          >
        </div>
      </div>
      <div class="flex gap-2" v-if="canEdit || canDelete">
        <button
          v-if="canEdit"
          @click="$emit('edit', comment)"
          class="text-brand-600 hover:text-brand-700 text-sm dark:text-brand-400"
        >
          Edit
        </button>
        <button
          v-if="canDelete"
          @click="confirmDelete(comment.id)"
          class="text-error-600 hover:text-error-700 text-sm"
        >
          Delete
        </button>
      </div>
    </div>
    <MarkdownContent class="mt-2" :source="comment.comment" placeholder="(empty comment)" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Swal from 'sweetalert2'
import MarkdownContent from '@/components/common/MarkdownContent.vue'

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
  },
})

const emit = defineEmits(['edit', 'delete'])

const fallbackAvatar = '/images/avatar.jpg'
const onImgError = (event) => {
  // If the URL is already the local fallback, leave it alone to avoid an infinite loop.
  if (event?.target?.src && event.target.src.includes(fallbackAvatar)) return
  if (event?.target) event.target.src = fallbackAvatar
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-us', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const isEdited = computed(() => {
  return props.comment.created_at !== props.comment.updated_at
})

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
      emit('delete', commentId)
    }
  })
}
</script>
