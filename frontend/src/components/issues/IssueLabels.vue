<template>
  <div class="flex flex-wrap items-center gap-2">
    <span v-if="loading" class="text-sm text-gray-400">Loading labels...</span>

    <template v-else>
      <span v-for="label in labels" :key="label.id"
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-white"
        :style="{ backgroundColor: label.color || '#64748b' }">
        {{ label.name }}
        <button v-if="removable" type="button" class="ml-0.5 opacity-80 hover:opacity-100"
          :title="`Remove ${label.name}`" @click="$emit('remove', label)">
          <i class="pi pi-times text-[10px]"></i>
        </button>
      </span>

      <span v-if="labels.length === 0" class="text-sm text-gray-400">No labels</span>
    </template>
  </div>
</template>

<script setup>
defineProps({
  labels: {
    type: Array,
    default: () => [],
  },
  removable: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['remove'])
</script>
