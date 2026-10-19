<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />

    <div class="space-y-5 sm:space-y-6">
      <router-link
        to="/activity-logs"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400"
      >
        <i class="pi pi-arrow-left"></i>
        Back to activity logs
      </router-link>

      <div v-if="loading" class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3 animate-pulse">
        <div class="space-y-5 lg:col-span-2">
          <div class="h-40 rounded-2xl bg-gray-200 dark:bg-gray-800"></div>
          <div class="h-48 rounded-2xl bg-gray-200 dark:bg-gray-800"></div>
        </div>
        <div class="space-y-5">
          <div class="h-52 rounded-2xl bg-gray-200 dark:bg-gray-800"></div>
          <div class="h-56 rounded-2xl bg-gray-200 dark:bg-gray-800"></div>
        </div>
      </div>

      <div
        v-else-if="error"
        class="rounded-2xl border border-gray-200 bg-white p-10 text-center dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <span
          class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-error-50 text-error-500 ring-1 ring-error-100 dark:bg-error-500/15 dark:ring-error-500/25"
        >
          <i class="pi pi-exclamation-circle text-xl"></i>
        </span>
        <h3 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">
          Activity not available
        </h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
          {{ error }}
        </p>
        <router-link
          to="/activity-logs"
          class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
        >
          <i class="pi pi-arrow-left"></i>
          Back to activity logs
        </router-link>
      </div>

      <div v-else-if="log" class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
          <!-- Summary -->
          <div
            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
          >
            <div class="flex items-start gap-4">
              <span
                class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl ring-1"
                :class="[styles.soft, styles.ring]"
              >
                <i :class="styles.icon" class="text-2xl"></i>
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="styles.soft"
                  >
                    {{ log.action_label || styles.label }}
                  </span>
                  <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-400"
                  >
                    {{ styles.category }}
                  </span>
                </div>
                <p class="mt-3 text-lg leading-relaxed text-gray-800 dark:text-white/90">
                  {{ log.description }}
                </p>
                <div
                  class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500 dark:text-gray-400"
                >
                  <span class="inline-flex items-center gap-1.5">
                    <i class="pi pi-calendar text-xs"></i>
                    {{ formatFullDate(log.created_at) }}
                  </span>
                  <span class="inline-flex items-center gap-1.5">
                    <i class="pi pi-clock text-xs"></i>
                    {{ relativeTime }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Context -->
          <ComponentCard title="Context" desc="The project and issue this event belongs to.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <router-link
                v-if="log.project"
                :to="`/projects/${log.project.id}`"
                class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-sm dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/40"
              >
                <span
                  class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl"
                  :style="projectTileStyle"
                >
                  <i class="pi pi-folder text-sm"></i>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">
                    Project
                  </span>
                  <span
                    class="mt-0.5 block truncate text-sm font-semibold text-gray-800 group-hover:text-brand-600 dark:text-white/90"
                  >
                    {{ log.project.name }}
                  </span>
                  <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                    {{ log.project.code }}
                  </span>
                </span>
                <i
                  class="pi pi-external-link flex-shrink-0 text-xs text-gray-300 transition group-hover:text-brand-500 dark:text-gray-600"
                ></i>
              </router-link>

              <router-link
                v-if="log.issue"
                :to="`/issues/${log.issue.id}`"
                class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-sm dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/40"
              >
                <span
                  class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                >
                  <i class="pi pi-bolt text-sm"></i>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">
                    Issue
                  </span>
                  <span
                    class="mt-0.5 block truncate text-sm font-semibold text-gray-800 group-hover:text-brand-600 dark:text-white/90"
                  >
                    {{ log.issue.issue_key || `#${log.issue.id}` }}
                  </span>
                  <span class="mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ log.issue.title }}
                  </span>
                  <span
                    v-if="log.issue.status_label"
                    class="mt-1.5 inline-flex items-center rounded-md bg-blue-light-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400"
                  >
                    {{ log.issue.status_label }}
                  </span>
                </span>
                <i
                  class="pi pi-external-link flex-shrink-0 text-xs text-gray-300 transition group-hover:text-brand-500 dark:text-gray-600"
                ></i>
              </router-link>

              <div
                v-if="!log.project && !log.issue"
                class="col-span-full rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400"
              >
                This event is not linked to a specific project or issue.
              </div>
            </div>
          </ComponentCard>

          <!-- Event data -->
          <ComponentCard title="Event Data" desc="The details captured when this action occurred.">
            <template #header>
              <button
                v-if="hasProperties"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-brand-300 hover:text-brand-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:border-brand-500/40"
                @click="copyRaw"
              >
                <i class="pi pi-copy"></i>
                Copy JSON
              </button>
            </template>

            <dl v-if="propertyEntries.length" class="divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="[key, value] in propertyEntries"
                :key="key"
                class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  {{ formatKey(key) }}
                </dt>
                <dd class="sm:col-span-2">
                  <pre
                    v-if="isPlainObject(value) || Array.isArray(value)"
                    class="max-w-full overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 font-mono text-xs leading-relaxed text-gray-700 dark:bg-white/[0.04] dark:text-gray-300"
                    >{{ formatValue(value) }}</pre
                  >
                  <span v-else class="break-words text-sm text-gray-800 dark:text-white/90">{{
                    formatValue(value)
                  }}</span>
                </dd>
              </div>
            </dl>

            <p v-else class="py-2 text-sm text-gray-500 dark:text-gray-400">
              No additional data was recorded for this event.
            </p>

            <div v-if="hasProperties" class="mt-4">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 transition hover:text-brand-700 dark:text-brand-400"
                @click="showRaw = !showRaw"
              >
                <i :class="showRaw ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                {{ showRaw ? 'Hide' : 'View' }} raw payload
              </button>
              <pre
                v-if="showRaw"
                class="mt-3 overflow-x-auto rounded-xl bg-gray-900 p-4 font-mono text-xs leading-relaxed text-gray-100"
                >{{ rawJson }}</pre
              >
            </div>
          </ComponentCard>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5">
          <ComponentCard title="Performed By">
            <div class="flex items-center gap-3">
              <img
                :src="log.user?.avatar || '/images/avatar.jpg'"
                @error="onAvatarError"
                alt=""
                class="h-12 w-12 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-gray-900"
              />
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">
                  {{ log.user?.name || 'System' }}
                </p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                  {{ log.user?.email || 'Automated activity' }}
                </p>
              </div>
            </div>
            <div v-if="userRole" class="mt-4 flex flex-wrap gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
              >
                <i class="pi pi-shield text-[10px]"></i>
                {{ userRole }}
              </span>
              <span
                v-if="log.user?.status"
                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-400"
              >
                {{ log.user.status_label || log.user.status }}
              </span>
            </div>
          </ComponentCard>

          <ComponentCard title="Event Information">
            <dl class="space-y-3">
              <div class="flex items-start justify-between gap-3">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Event ID</dt>
                <dd class="text-sm font-medium text-gray-800 dark:text-white/90">#{{ log.id }}</dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Action</dt>
                <dd>
                  <code
                    class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300"
                    >{{ log.action }}</code
                  >
                </dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Category</dt>
                <dd class="text-sm font-medium text-gray-800 dark:text-white/90">
                  {{ styles.category }}
                </dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Recorded</dt>
                <dd class="text-right text-sm font-medium text-gray-800 dark:text-white/90">
                  {{ formatFullDate(log.created_at) }}
                </dd>
              </div>
            </dl>
          </ComponentCard>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import { useActivityLogStore } from '@/stores/activityLogs'
import { getActionStyles } from '@/components/activity/activityMeta'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'

const route = useRoute()
const store = useActivityLogStore()

const pageTitle = ref('Activity Details')
const log = ref(null)
const loading = ref(true)
const error = ref(null)
const showRaw = ref(false)

const styles = computed(() => getActionStyles(log.value?.action))

const propertyEntries = computed(() => {
  const props = log.value?.properties
  if (!props || typeof props !== 'object') return []
  return Object.entries(props)
})

const hasProperties = computed(() => propertyEntries.value.length > 0)

const rawJson = computed(() => JSON.stringify(log.value?.properties ?? {}, null, 2))

const userRole = computed(() => {
  const user = log.value?.user
  if (!user) return null
  if (Array.isArray(user.roles) && user.roles.length) return user.roles[0]
  return user.role || null
})

const projectTileStyle = computed(() => {
  const color = log.value?.project?.color
  if (!color) {
    return { backgroundColor: 'rgba(70, 95, 255, 0.1)', color: '#465fff' }
  }
  return { backgroundColor: `${color}1a`, color }
})

const relativeTime = computed(() => timeAgo(log.value?.created_at))

const formatKey = (key) =>
  String(key)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const isPlainObject = (value) => value !== null && typeof value === 'object'

const formatValue = (value) => {
  if (value === null || value === undefined || value === '') return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (Array.isArray(value) || typeof value === 'object') {
    return JSON.stringify(value, null, 2)
  }
  return String(value)
}

const formatFullDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleString('en-US', {
    weekday: 'short',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function timeAgo(dateString) {
  if (!dateString) return '—'
  const diff = Math.floor((Date.now() - new Date(dateString)) / 1000)
  if (diff < 0) return 'just now'
  if (diff < 60) return `${diff} seconds ago`
  if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const fallbackAvatar = '/images/avatar.jpg'
const onAvatarError = (event) => {
  if (event?.target?.src && event.target.src.includes(fallbackAvatar)) return
  if (event?.target) event.target.src = fallbackAvatar
}

const copyRaw = async () => {
  try {
    await navigator.clipboard.writeText(rawJson.value)
    toast.success('Event data copied to clipboard')
  } catch {
    toast.error('Unable to copy event data')
  }
}

const loadLog = async () => {
  loading.value = true
  error.value = null
  try {
    log.value = await store.fetchLog(route.params.id)
  } catch (err) {
    const status = err?.response?.status
    if (status === 403) {
      error.value = 'You do not have permission to view this activity event.'
    } else if (status === 404) {
      error.value = 'This activity event does not exist or has been removed.'
    } else {
      error.value = 'Something went wrong while loading this event. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadLog)
</script>
