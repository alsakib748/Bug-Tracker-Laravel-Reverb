<template>
  <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <span
          class="flex h-7 w-7 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-white/5 dark:ring-gray-800">
          <i class="pi pi-filter text-xs text-gray-500 dark:text-gray-400"></i>
        </span>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
        <span v-if="activeCount"
          class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-[11px] font-semibold text-white">
          {{ activeCount }}
        </span>
      </div>
      <button v-if="activeCount" type="button"
        class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 transition hover:text-error-600 dark:text-gray-400 dark:hover:text-error-400"
        @click="clearAll">
        <i class="pi pi-times"></i>
        Clear all
      </button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="relative">
        <i class="pi pi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
        <InputText v-model="filters.search" placeholder="Search activity..." class="w-full !pl-9" @input="apply" />
      </div>

      <Select v-model="filters.action" :options="ACTION_FILTER_GROUPS" optionLabel="label" optionValue="value"
        optionGroupLabel="label" optionGroupChildren="options" placeholder="All actions" showClear class="w-full"
        @change="apply" />

      <Select v-model="filters.user_id" :options="usersList" optionLabel="name" optionValue="id" placeholder="All users"
        showClear class="w-full" @change="apply">
        <template #value="{ value, placeholder }">
          <div v-if="value" class="flex items-center">
            <img :src="selectedUser?.avatar || '/images/avatar.jpg'" @error="onAvatarError" alt=""
              class="mr-2 h-6 w-6 rounded-full object-cover" />
            <span>{{ selectedUser?.name || `User #${value}` }}</span>
          </div>
          <span v-else class="text-gray-500">{{ placeholder }}</span>
        </template>
        <template #option="{ option }">
          <div class="flex items-center">
            <img :src="option.avatar || '/images/avatar.jpg'" @error="onAvatarError" alt=""
              class="mr-2 h-6 w-6 rounded-full object-cover" />
            <span>{{ option.name }}</span>
          </div>
        </template>
      </Select>

      <DatePicker v-model="filters.date" selectionMode="range" placeholder="Date range" dateFormat="yy-mm-dd" showIcon
        class="w-full" @update:modelValue="apply" />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import { useUserStore } from '@/stores/users'
import { ACTION_FILTER_GROUPS } from './activityMeta'

const emit = defineEmits(['filter'])

const filters = reactive({
  search: '',
  action: null,
  user_id: null,
  date: null,
})

const usersList = ref([])
const userStore = useUserStore()

const selectedUser = computed(() => usersList.value.find((user) => user.id === filters.user_id))

const activeCount = computed(() => {
  let count = 0
  if (filters.search) count += 1
  if (filters.action) count += 1
  if (filters.user_id) count += 1
  if (filters.date && filters.date.length === 2 && filters.date[0] && filters.date[1]) count += 1
  return count
})

const apply = () => {
  const params = {
    search: filters.search || undefined,
    action: filters.action || undefined,
    user_id: filters.user_id || undefined,
  }
  if (filters.date && filters.date.length === 2) {
    const [start, end] = filters.date
    if (start) params.from = start.toISOString().split('T')[0]
    if (end) params.to = end.toISOString().split('T')[0]
  }
  emit('filter', params)
}

const clearAll = () => {
  filters.search = ''
  filters.action = null
  filters.user_id = null
  filters.date = null
  emit('filter', {})
}

const fallbackAvatar = '/images/avatar.jpg'
const onAvatarError = (event) => {
  if (event?.target?.src && event.target.src.includes(fallbackAvatar)) return
  if (event?.target) event.target.src = fallbackAvatar
}

const loadUsers = async () => {
  try {
    // Scoped endpoint authorized on "view activity logs" — safe for Viewer+.
    usersList.value = await userStore.fetchActivityFilterUsers()
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

onMounted(loadUsers)
</script>
