<template>
  <div class="mt-4">
    <!-- Drag & Drop Area -->
    <div class="border-2 border-dashed rounded-lg p-6 text-center transition-colors" :class="{
      'border-gray-300 bg-gray-50 hover:bg-gray-100': !isDragging,
      'border-blue-500 bg-blue-50': isDragging,
      'border-red-400 bg-red-50': error,
    }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
      <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden" :accept="acceptedTypes" />

      <div v-if="!selectedFile && !uploading">
        <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-2"></i>
        <p class="text-gray-600">
          Drag & drop a file here, or
          <button type="button" @click="$refs.fileInput.click()" class="text-blue-600 hover:underline font-medium">
            browse
          </button>
        </p>
        <p class="text-xs text-gray-400 mt-1">
          Allowed: {{ acceptedTypes }} • Max {{ maxSizeMB }} MB
        </p>
      </div>

      <!-- File selected (ready to upload) -->
      <div v-else-if="selectedFile && !uploading" class="flex items-center justify-center gap-3">
        <i :class="getFileIcon(selectedFile.type)" class="text-2xl text-blue-600"></i>
        <div class="text-left">
          <p class="text-sm font-medium text-gray-800">{{ selectedFile.name }}</p>
          <p class="text-xs text-gray-500">{{ formatSize(selectedFile.size) }}</p>
        </div>
        <button type="button" @click="clearFile" class="text-red-500 hover:text-red-700" title="Remove file">
          <i class="pi pi-times"></i>
        </button>
        <button type="button" @click="upload" :disabled="uploading"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
          Upload
        </button>
      </div>

      <!-- Uploading -->
      <div v-else-if="uploading" class="flex flex-col items-center gap-2">
        <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        <p class="text-sm text-gray-600">Uploading...</p>
        <div class="w-full max-w-xs bg-gray-200 rounded-full h-2.5">
          <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"></div>
        </div>
        <p class="text-xs text-gray-500">{{ uploadProgress }}%</p>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="text-red-500 text-sm mt-2">
      <i class="pi pi-exclamation-circle mr-1"></i> {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  acceptedTypes: {
    type: String,
    default: 'image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.log,.json,.xml,.mp4,.webm,.csv,.xlsx',
  },
  maxSizeMB: {
    type: Number,
    default: 20,
  },
});

const emit = defineEmits(['upload', 'error']);

const fileInput = ref(null);
const selectedFile = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const error = ref(null);
const isDragging = ref(false);

const maxSizeBytes = props.maxSizeMB * 1024 * 1024;

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    validateAndSetFile(file);
  }
};

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file) {
    validateAndSetFile(file);
  }
};

const validateAndSetFile = (file) => {
  error.value = null;

  // Check file size
  if (file.size > maxSizeBytes) {
    error.value = `File too large. Maximum ${props.maxSizeMB} MB allowed.`;
    emit('error', error.value);
    return;
  }

  selectedFile.value = file;
};

const clearFile = () => {
  selectedFile.value = null;
  error.value = null;
  if (fileInput.value) {
    fileInput.value = null;
  }
};

const upload = async () => {
  if (!selectedFile.value || uploading.value || props.disabled) return;

  uploading.value = true;
  uploadProgress.value = 0;
  error.value = null;

  try {
    // Simulate progress (optional – real progress requires axios onUploadProgress)
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10;
      }
    }, 200);

    await emit('upload', selectedFile.value);

    clearInterval(progressInterval);
    uploadProgress.value = 100;

    // Small delay to show 100% before clearing
    setTimeout(() => {
      clearFile();
      uploading.value = false;
      uploadProgress.value = 0;
    }, 500);
  } catch (err) {
    error.value = err.response?.data?.message || 'Upload failed. Please try again.';
    emit('error', error.value);
    uploading.value = false;
    uploadProgress.value = 0;
  }
};

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileIcon = (mimeType) => {
  if (!mimeType) return 'pi pi-file';
  if (mimeType.startsWith('image/')) return 'pi pi-image';
  if (mimeType.startsWith('video/')) return 'pi pi-video';
  if (mimeType === 'application/pdf') return 'pi pi-file-pdf';
  if (mimeType.includes('word') || mimeType.includes('document')) return 'pi pi-file-word';
  if (mimeType.includes('excel') || mimeType.includes('sheet')) return 'pi pi-file-excel';
  if (mimeType === 'application/zip' || mimeType === 'application/x-rar-compressed') return 'pi pi-box';
  if (mimeType === 'text/plain' || mimeType.startsWith('text/')) return 'pi pi-file';
  return 'pi pi-file';
};
</script>

<style scoped>
/* Smooth transitions */
.border-dashed {
  transition: all 0.2s ease;
}

/* Optional: Add a subtle glow on drag */
.border-blue-500 {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
</style>
