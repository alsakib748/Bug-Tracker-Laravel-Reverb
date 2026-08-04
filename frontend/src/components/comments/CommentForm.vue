<template>
  <form @submit.prevent="submit" class="mt-4">
    <div>
      <label for="comment" class="block text-sm font-medium text-gray-700">Add a comment</label>
      <Textarea id="comment" v-model="content" rows="3"
        :placeholder="editing ? 'Edit your comment...' : 'Write your comment...'" class="w-full mt-1"
        :class="{ 'p-invalid': error }" @keydown.ctrl.enter="submit" />
      <small v-if="error" class="text-red-500">{{ error }}</small>
    </div>
    <div class="flex justify-end gap-2 mt-2">
      <Button v-if="editing" type="button" label="Cancel" class="p-button-text" @click="$emit('cancelEdit')" />
      <Button type="submit" :loading="submitting" :label="editing ? 'Update' : 'Post Comment'" icon="pi pi-send"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" />
    </div>
  </form>
</template>

<script setup>

import { ref, watch } from 'vue';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const props = defineProps({
  initialContent: {
    type: String,
    default: '',
  },
  editing: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['submit', 'cancelEdit']);

const content = ref(props.initialContent);

const submitting = ref(false);

const error = ref(null);

watch(() => props.initialContent, (newVal) => {
  content.value = newVal;
});

const submit = async () => {
  error.value = null;
  const trimmed = content.value.trim();
  if (!trimmed) {
    error.value = 'Comment cannot be empty.';
    return;
  }
  try {
    submitting.value = true;
    await emit('submit', trimmed);
    content.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save comment.';
  } finally {
    submitting.value = false;
  }
}

</script>
