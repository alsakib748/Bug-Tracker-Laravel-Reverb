<template>
  <div>
    <div v-if="loading" class="flex justify-center py-2">
      <i class="pi pi-spin pi-spinner text-blue-600" style="font-size: 1.25rem"></i>
    </div>

    <div v-else-if="watchers.length === 0" class="text-sm text-gray-400">
      No one is watching this issue.
    </div>

    <ul v-else class="flex flex-wrap gap-2">
      <li
        v-for="watcher in watchers"
        :key="watcher.id"
        class="inline-flex items-center gap-2 px-2 py-1 bg-gray-100 rounded-full text-sm text-gray-700"
      >
        <img
          v-if="watcher.avatar"
          :src="watcher.avatar"
          :alt="watcher.name"
          class="w-5 h-5 rounded-full object-cover"
        />
        <i v-else class="pi pi-user text-gray-400"></i>
        {{ watcher.name }}
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  watchers: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})
</script>
