<template>
  <AdminLayout>
    <PageBreadcrumb title="Projects" :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Projects" :desc="resultSummary">
        <div class="mb-4 flex justify-between">
          <div class="flex gap-4 items-center">
              <i class="pi pi-search" aria-hidden="true"></i>
            <InputText
              v-model="filters.global.value"
              placeholder="Search projects..."
              class="w-64"
            />
          </div>
          <router-link
            v-if="can('create projects')"
            to="/projects/create"
            class="bg-blue-600 text-white px-4 py-2 rounded-md inline-flex items-center gap-2"
          >
            + New Project
          </router-link>
        </div>

        <AppSkeleton v-if="loading && !projects.length" :rows="6" />

        <EmptyState
          v-else-if="!projects.length"
          title="No projects yet"
          description="Create the first project to start organizing issues for this team."
        />

        <DataTable
          v-else
          :value="projects"
          :loading="loading"
          v-model:filters="filters"
          filterDisplay="menu"
          tableStyle="min-width: 50rem"
          responsiveLayout="scroll"
          dataKey="id"
          rowHover
          class="p-4"
        >
          <Column field="name" header="Name" sortable filter>
            <template #body="{ data }">
              <router-link :to="`/projects/${data.id}`" class="text-blue-400">
                {{ data.name }}
              </router-link>
            </template>
          </Column>

          <Column field="code" header="Code" sortable filter></Column>

          <Column field="status" header="Status" sortable filter>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full ${data.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`"
              >
                {{ data.status_label }}
              </span>
            </template>
          </Column>

          <Column field="created_by.name" header="Creator" sortable> </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>

          <Column header="Actions" style="min-width: 150px">
            <template #body="{ data }">
              <router-link
                v-if="can('update projects')"
                :to="`/projects/${data.id}/edit`"
                class="text-blue-600 mr-2 hover:underline"
                aria-label="Edit project"
              >
                <i class="pi pi-pen-to-square" aria-hidden="true"></i>
              </router-link>
              <button
                v-if="can('delete projects')"
                @click="confirmDelete(data)"
                class="text-red-600 hover:underline"
                aria-label="Delete project"
              >
                <i class="pi pi-trash" aria-hidden="true"></i>
              </button>
              <span
                v-if="!can('update projects') && !can('delete projects')"
                class="text-xs text-gray-400"
              >
                View only
              </span>
            </template>
          </Column>
        </DataTable>

        <!-- Infinite-scroll footer -->
        <div
          v-if="projects.length"
          class="flex items-center justify-center gap-2.5 pt-4 text-sm"
          aria-live="polite"
        >
          <template v-if="loadingMore">
            <i class="pi pi-spin pi-spinner text-brand-500" aria-hidden="true"></i>
            <span class="text-gray-500 dark:text-gray-400">Loading more projects…</span>
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
import { useProjectStore } from '@/stores/projects'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import Swal from 'sweetalert2'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()

const currentPageTitle = ref('Project List')

const projectStore = useProjectStore()

const { projects, loading, loadingMore, pagination } = storeToRefs(projectStore)

const PER_PAGE = 15
const sentinel = ref(null)

const filters = reactive({
  global: {
    value: null,
    matchMode: 'contains',
  },
})

const resultSummary = computed(() => {
  const total = pagination.value?.total ?? 0
  const shown = projects.value.length
  if (!total) return ''
  if (shown >= total) return total === 1 ? '1 project' : `${total} projects`
  return `Showing ${shown} of ${total} projects`
})

const hasMore = computed(() => pagination.value.current_page < pagination.value.last_page)

const fetchAll = async (params = {}, { append = false } = {}) => {
  try {
    await projectStore.fetchAllProjects(params, { append })
  } catch (error) {
    console.error('Project List Error', error)
  }
}

// Replace the whole list (used on mount and after delete refreshes).
const reload = (extra = {}) => fetchAll({ page: 1, per_page: PER_PAGE, ...extra })

const loadMore = () => {
  if (loading.value || loadingMore.value || !hasMore.value) return
  fetchAll({ page: pagination.value.current_page + 1, per_page: PER_PAGE }, { append: true })
}

// If the sentinel is still near the viewport after a fetch (e.g. a short page
// that doesn't fill the screen), keep loading until the viewport is filled.
watch([projects, loadingMore], () => {
  if (loading.value || loadingMore.value || !hasMore.value || !sentinel.value) return
  const rect = sentinel.value.getBoundingClientRect()
  if (rect.top < window.innerHeight + 300) loadMore()
})

const confirmDelete = async (project) => {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete "${project.name}". This cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
  })

  if (result.isConfirmed) {
    try {
      await projectStore.deleteProject(project.id)
      // Remove locally so the user keeps their scroll position.
      projectStore.removeProjectFromList(project.id)
      Swal.fire('Deleted!', `"${project.name}" has been deleted.`, 'success')
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to delete project.'
      Swal.fire('Error!', message, 'error')
    }
  }
}

let scrollObserver = null

onMounted(() => {
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
