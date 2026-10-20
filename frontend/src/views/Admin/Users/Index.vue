<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" :description="resultSummary" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Users">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">All Users</h3>
          <router-link
            to="/users/create"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm"
          >
            + Add User
          </router-link>
        </div>

        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
          <InputText
            v-model="filters.search"
            placeholder="Search users..."
            @input="debouncedApplyFilters"
          />
          <Select
            v-model="filters.role"
            :options="roleOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Role"
            clearable
            @change="applyFilters"
          />
          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            clearable
            @change="applyFilters"
          />
        </div>

        <AppSkeleton v-if="loading && !users.length" :rows="6" />

        <EmptyState
          v-else-if="!users.length"
          title="No users found"
          description="Try adjusting the search, role, or status filters."
        />

        <DataTable
          v-else
          :value="users"
          :loading="loading"
          @sort="onSort"
          sortField="id"
          :sortOrder="-1"
          dataKey="id"
          rowHover
          class="p-4"
        >
          <Column field="id" header="#" sortable style="width: 60px" />
          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <img
                  :src="data.avatar || '/images/avatar.jpg'"
                  @error="onImgError"
                  class="w-8 h-8 rounded-full"
                />
                {{ data.name }}
              </div>
            </template>
          </Column>
          <Column field="email" header="Email" sortable />
          <Column field="role" header="Role" sortable>
            <template #body="{ data }">
              {{ data.role_label }}
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full ${data.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`"
              >
                {{ data.status_label }}
              </span>
            </template>
          </Column>
          <Column field="created_at" header="Joined" sortable>
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>
          <Column header="Actions" style="min-width: 150px">
            <template #body="{ data }">
              <router-link :to="`/users/${data.id}/edit`" class="text-blue-600 mr-3"
                >Edit</router-link
              >
              <button @click="confirmDelete(data)" class="text-red-600">Delete</button>
            </template>
          </Column>
        </DataTable>

        <!-- Infinite-scroll footer -->
        <div
          v-if="users.length"
          class="flex items-center justify-center gap-2.5 pt-4 text-sm"
          aria-live="polite"
        >
          <template v-if="loadingMore">
            <i class="pi pi-spin pi-spinner text-brand-500" aria-hidden="true"></i>
            <span class="text-gray-500 dark:text-gray-400">Loading more users…</span>
          </template>
          <span v-else-if="!hasMore" class="text-gray-400 dark:text-gray-500">
            You've reached the end
          </span>
        </div>

        <!-- Sentinel: observed to fetch the next page while scrolling -->
        <div ref="sentinel" class="h-px w-full" aria-hidden="true"></div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useUserStore } from '@/stores/users'
import { useRoleOptions } from '@/composables/useRoleOptions'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Swal from 'sweetalert2'
import { debounce } from '@/services/realtime'

const userStore = useUserStore()
const { users, loading, loadingMore, pagination } = storeToRefs(userStore)
const pageTitle = ref('Users')

const PER_PAGE = 15
const sentinel = ref(null)

const filters = reactive({
  search: '',
  role: null,
  status: null,
})

const { roleOptions, load: loadRoles } = useRoleOptions()

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const resultSummary = computed(() => {
  const total = pagination.value?.total ?? 0
  const shown = users.value.length
  if (!total) return ''
  if (shown >= total) return total === 1 ? '1 user' : `${total} users`
  return `Showing ${shown} of ${total} users`
})

const hasMore = computed(() => pagination.value.current_page < pagination.value.last_page)

let lastSort = '-id'

const fetchUsers = async (params = {}, { append = false } = {}) => {
  try {
    await userStore.fetchUsers({ ...filters, ...params }, { append })
  } catch (error) {
    console.error('Error fetching users:', error)
  }
}

// Replace the whole list (used on mount, filter/sort changes, retry).
const reload = (extra = {}) => fetchUsers({ sort: lastSort, page: 1, per_page: PER_PAGE, ...extra })

const loadMore = () => {
  if (loading.value || loadingMore.value || !hasMore.value) return
  fetchUsers(
    { sort: lastSort, page: pagination.value.current_page + 1, per_page: PER_PAGE },
    { append: true },
  )
}

// If the sentinel is still near the viewport after a fetch (e.g. a short page
// that doesn't fill the screen), keep loading until the viewport is filled.
watch([users, loadingMore], () => {
  if (loading.value || loadingMore.value || !hasMore.value || !sentinel.value) return
  const rect = sentinel.value.getBoundingClientRect()
  if (rect.top < window.innerHeight + 300) loadMore()
})

const applyFilters = () => {
  reload()
}

const debouncedApplyFilters = debounce(applyFilters, 300)

const onSort = (event) => {
  const direction = event.sortOrder === 1 ? '' : '-'
  lastSort = direction + event.sortField
  reload({ sort: lastSort })
}

const fallbackAvatar = '/images/avatar.jpg'
const onImgError = (event) => {
  if (event?.target?.src && event.target.src.includes(fallbackAvatar)) return
  if (event?.target) event.target.src = fallbackAvatar
}

const confirmDelete = async (user) => {
  if (user.is_admin && user.id === 1) {
    Swal.fire('Cannot delete the default admin user.', '', 'error')
    return
  }
  const result = await Swal.fire({
    title: 'Delete User?',
    text: `Delete "${user.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete',
  })
  if (result.isConfirmed) {
    try {
      await userStore.deleteUser(user.id)
      // Remove locally so the user keeps their scroll position.
      userStore.removeUserFromList(user.id)
      Swal.fire('Deleted', 'User deleted.', 'success')
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'Failed to delete.', 'error')
    }
  }
}

let scrollObserver = null

onMounted(() => {
  loadRoles()
  reload()

  // Fetch the next page when the sentinel scrolls into view (with a 300px
  // lead so the next page is already arriving as the user reaches the end).
  scrollObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) loadMore()
    },
    { rootMargin: '300px 0px' },
  )
  if (sentinel.value) scrollObserver.observe(sentinel.value)
})

onUnmounted(() => {
  if (scrollObserver) scrollObserver.disconnect()
})
</script>
