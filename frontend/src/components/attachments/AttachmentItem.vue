<!-- <template>
  <div class="flex items-center justify-between border-b border-gray-100 py-3">
    <div class="flex items-center space-x-3 flex-1 min-w-0">
      <i :class="getIcon(attachment.mime_type)" class="text-2xl text-gray-500"></i>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-800 truncate">{{ attachment.file_name }}</p>
        <p class="text-xs text-gray-500">
          {{ attachment.file_size_formatted || formatSize(attachment.file_size) }}
          • Uploaded by {{ attachment.user?.name || 'Unknown' }}
          <span class="ml-1">{{ timeAgo(attachment.created_at) }}</span>
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a :href="attachment.download_url" class="text-blue-600 hover:text-blue-800" title="Download">
        <i class="pi pi-download"></i>
      </a>
      <button v-if="canDelete" @click="confirmDelete" class="text-red-600 hover:text-red-800" title="Delete">
        <i class="pi pi-trash"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  attachment: { type: Object, required: true },
});

const emit = defineEmits(['delete']);

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const canDelete = computed(() => {
  return user.value?.isAdmin || user.value?.id === props.attachment.user_id;
});

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const timeAgo = (dateString) => {
  const diff = Math.floor((Date.now() - new Date(dateString)) / 1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
};

const getIcon = (mime) => {
  if (mime.startsWith('image/')) return 'pi pi-image';
  if (mime.startsWith('video/')) return 'pi pi-video';
  if (mime === 'application/pdf') return 'pi pi-file-pdf';
  if (mime.includes('word') || mime.includes('document')) return 'pi pi-file-word';
  if (mime.includes('excel') || mime.includes('sheet')) return 'pi pi-file-excel';
  if (mime === 'application/zip' || mime === 'application/x-rar-compressed') return 'pi pi-box';
  if (mime === 'text/plain' || mime.startsWith('text/')) return 'pi pi-file';
  return 'pi pi-file';
};

const confirmDelete = () => {
  if (confirm(`Delete "${props.attachment.file_name}"?`)) {
    emit('delete', props.attachment.id);
  }
};
</script> -->

<template>
  <div class="flex items-center justify-between border-b border-gray-100 py-3">
    <div class="flex items-center space-x-3 flex-1 min-w-0">
      <i :class="getIcon(attachment.mime_type)" class="text-2xl text-gray-500"></i>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-800 truncate">
          <!-- File name acts as a clickable link for preview -->
          <a :href="attachment.download_url" target="_blank" class="hover:text-blue-600 hover:underline"
            :download="attachment.file_name">
            {{ attachment.file_name }}
          </a>
        </p>
        <p class="text-xs text-gray-500">
          {{ attachment.file_size_formatted || formatSize(attachment.file_size) }}
          • Uploaded by {{ attachment.user?.name || 'Unknown' }}
          <span class="ml-1">{{ timeAgo(attachment.created_at) }}</span>
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <!-- Preview (for images) -->
      <button v-if="isImage" @click="openPreview" class="text-gray-500 hover:text-blue-600" title="Preview">
        <i class="pi pi-eye"></i>
      </button>
      <!-- Download -->
      <a :href="attachment.download_url" class="text-blue-600 hover:text-blue-800" title="Download">
        <i class="pi pi-download"></i>
      </a>
      <!-- Delete -->
      <button v-if="canDelete" @click="confirmDelete" class="text-red-600 hover:text-red-800" title="Delete">
        <i class="pi pi-trash"></i>
      </button>
    </div>

    <!-- Image Preview Modal (optional) -->
    <Dialog v-model:visible="showPreview" :header="attachment.file_name" modal position="center" appendTo="body"
      :style="{ width: '90vw', maxWidth: '800px' }" :baseZIndex="1000000">
      <img :src="attachment.download_url" class="w-full h-auto" />
    </Dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import Dialog from 'primevue/dialog';

const props = defineProps({
  attachment: { type: Object, required: true },
});

const emit = defineEmits(['delete']);

const authStore = useAuthStore();
const user = computed(() => authStore.user);
const showPreview = ref(false);

const isImage = computed(() => {
  return props.attachment.mime_type?.startsWith('image/');
});

const canDelete = computed(() => {
  return user.value?.isAdmin || user.value?.id === props.attachment.user_id;
});

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const timeAgo = (dateString) => {
  const diff = Math.floor((Date.now() - new Date(dateString)) / 1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
};

const getIcon = (mime) => {
  if (mime.startsWith('image/')) return 'pi pi-image';
  if (mime.startsWith('video/')) return 'pi pi-video';
  if (mime === 'application/pdf') return 'pi pi-file-pdf';
  if (mime.includes('word') || mime.includes('document')) return 'pi pi-file-word';
  if (mime.includes('excel') || mime.includes('sheet')) return 'pi pi-file-excel';
  if (mime === 'application/zip' || mime === 'application/x-rar-compressed') return 'pi pi-box';
  if (mime === 'text/plain' || mime.startsWith('text/')) return 'pi pi-file';
  return 'pi pi-file';
};

const openPreview = () => {
  showPreview.value = true;
};

const confirmDelete = () => {
  if (confirm(`Delete "${props.attachment.file_name}"?`)) {
    emit('delete', props.attachment.id);
  }
};
</script>
