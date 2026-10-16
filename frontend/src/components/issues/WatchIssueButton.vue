<template>
  <Button :label="isWatching ? 'Watching' : 'Watch'" :icon="isWatching ? 'pi pi-eye' : 'pi pi-eye-slash'"
    :loading="toggling" :class="isWatching ? 'p-button-outlined' : 'bg-blue-600 hover:bg-blue-700 text-white'"
    class="px-4 py-2 rounded-md" @click="$emit('toggle')" />
</template>

<script setup>
import Button from 'primevue/button'

defineProps({
  isWatching: {
    type: Boolean,
    default: false,
  },
  toggling: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggle'])
</script>
