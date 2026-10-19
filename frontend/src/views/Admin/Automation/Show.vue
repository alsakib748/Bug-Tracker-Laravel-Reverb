<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="currentPageTitle"
      description="Review the conditions, actions, and recent executions of this automation rule."
    />
    <div class="space-y-5 sm:space-y-6">
      <AppSkeleton v-if="automationStore.loading" :rows="6" />

      <template v-else-if="rule">
        <ComponentCard title="Rule">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-ink dark:text-gray-300">{{ rule.name }}</h3>
              <p class="mt-1 text-sm text-ink-muted dark:text-gray-400">
                {{ rule.description || 'No description.' }}
              </p>
              <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <AppBadge size="sm" :color="rule.is_enabled ? 'success' : 'light'">
                  {{ rule.is_enabled ? 'Enabled' : 'Disabled' }}
                </AppBadge>
                <AppBadge size="sm" color="primary">
                  {{ triggerLabel(rule.trigger_type) }}
                </AppBadge>
                <AppBadge size="sm" color="light">v{{ rule.version }}</AppBadge>
                <AppBadge v-if="rule.run_on_actor_changes" size="sm" color="warning">
                  reacts to its own changes
                </AppBadge>
              </div>
            </div>

            <div v-if="canManage" class="flex gap-2">
              <AppButton size="sm" variant="outline" :onClick="goEdit">Edit</AppButton>
              <AppButton size="sm" variant="outline" :onClick="toggle">
                <i :class="rule.is_enabled ? 'pi pi-pause' : 'pi pi-play'" aria-hidden="true"></i>
                {{ rule.is_enabled ? 'Disable' : 'Enable' }}
              </AppButton>
              <AppButton size="sm" variant="danger" :onClick="confirmDelete">
                <i class="pi pi-trash" aria-hidden="true"></i>
                Delete
              </AppButton>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <h4 class="text-sm font-semibold text-ink dark:text-gray-300">
                Conditions ({{ rule.conditions?.match || 'all' }})
              </h4>
              <ul
                v-if="rule.conditions?.rules?.length"
                class="mt-2 space-y-1 text-sm text-ink-muted dark:text-gray-400"
              >
                <li
                  v-for="(condition, index) in rule.conditions.rules"
                  :key="index"
                  class="rounded bg-gray-50 px-3 py-1 dark:bg-white/[0.03]"
                >
                  <span class="font-medium">{{ humanize(condition.field) }}</span>
                  {{ operatorLabel(condition.operator) }}
                  <span
                    v-if="condition.value !== null && condition.value !== ''"
                    class="font-medium"
                  >
                    {{ valueLabel(condition.field, condition.value) }}
                  </span>
                </li>
              </ul>
              <p v-else class="mt-2 text-sm text-ink-muted dark:text-gray-400">
                Runs on every matching trigger.
              </p>
            </div>

            <div>
              <h4 class="text-sm font-semibold text-ink dark:text-gray-300">Actions</h4>
              <ul class="mt-2 space-y-1 text-sm text-ink-muted dark:text-gray-400">
                <li
                  v-for="(action, index) in rule.actions"
                  :key="index"
                  class="rounded bg-gray-50 px-3 py-1 dark:bg-white/[0.03]"
                >
                  {{ actionLabel(action.type) }}
                  <span class="text-ink-muted dark:text-gray-400"
                    >— {{ describeConfig(action) }}</span
                  >
                </li>
              </ul>
            </div>
          </div>
        </ComponentCard>

        <ComponentCard title="Recent executions">
          <DataTable
            :value="automationStore.executions"
            :loading="loadingExecutions"
            paginator
            :rows="10"
            responsiveLayout="scroll"
          >
            <Column field="created_at" header="When">
              <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <AppBadge size="sm" :color="executionStatusColor(data.status)">
                  {{ humanize(data.status) }}
                </AppBadge>
              </template>
            </Column>
            <Column field="event_type" header="Trigger">
              <template #body="{ data }">{{ triggerLabel(data.event_type) }}</template>
            </Column>
            <Column header="Result">
              <template #body="{ data }">
                <template v-if="data.actions?.length">
                  <AppBadge
                    v-for="action in data.actions"
                    :key="action.id"
                    size="sm"
                    className="mr-1"
                    :color="executionStatusColor(action.status)"
                  >
                    {{ actionLabel(data.action_type || action.action_type) }}
                  </AppBadge>
                </template>
                <span v-else-if="data.error_code" class="text-xs text-red-600">{{
                  data.error_code
                }}</span>
                <span v-else class="text-xs text-ink-muted dark:text-gray-400">—</span>
              </template>
            </Column>
          </DataTable>

          <EmptyState
            v-if="!automationStore.executions.length && !loadingExecutions"
            title="No executions yet"
            description="This rule has not run yet."
          />
        </ComponentCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAutomationStore } from '@/stores/automation'
import { usePermissions } from '@/composables/usePermissions'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const currentPageTitle = ref('Automation Rule')
const route = useRoute()
const router = useRouter()
const automationStore = useAutomationStore()
const { hasPermission } = usePermissions()
const canManage = computed(() => hasPermission('manage automation rules'))

const ruleId = route.params.id
const loadingExecutions = ref(false)

const rule = computed(() => automationStore.rule)

const humanize = (value) =>
  String(value || '')
    .replace(/[._]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const triggerLabel = (value) =>
  automationStore.options.triggers.find((trigger) => trigger.value === value)?.label ||
  humanize(value)

const operatorLabel = (value) =>
  automationStore.options.operators.find((operator) => operator.value === value)?.label || value

const actionLabel = (value) =>
  automationStore.options.actions.find((action) => action.value === value)?.label || humanize(value)

const valueLabel = (field, value) => {
  if (Array.isArray(value)) return value.map((item) => valueLabel(field, item)).join(', ')
  const type = automationStore.options.fields?.[field]?.type
  if (type === 'user' || field === 'assignee') return `#${value}`
  return value
}

const describeConfig = (action) => {
  const config = action.config || {}
  return Object.entries(config)
    .map(([key, value]) => `${humanize(key)}: ${Array.isArray(value) ? value.join(', ') : value}`)
    .join(' · ')
}

const EXECUTION_STATUS_COLOR = {
  succeeded: 'success',
  partially_failed: 'warning',
  failed: 'error',
  skipped: 'light',
  running: 'info',
  queued: 'info',
}

const executionStatusColor = (status) => EXECUTION_STATUS_COLOR[status] || 'light'

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '—')

const goEdit = () => router.push(`/automation/${rule.value.id}/edit`)

const toggle = async () => {
  try {
    const updated = await automationStore.toggle(rule.value)
    automationStore.replace(updated)
    toast.success(updated.is_enabled ? 'Rule enabled.' : 'Rule disabled.')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to update the rule.')
  }
}

const confirmDelete = async () => {
  const result = await Swal.fire({
    title: 'Delete rule?',
    text: 'This automation rule will be permanently removed.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'Delete',
  })

  if (!result.isConfirmed) return

  try {
    await automationStore.remove(ruleId)
    toast.success('Rule deleted.')
    router.push('/automation')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to delete the rule.')
  }
}

onMounted(async () => {
  await automationStore.loadOptions()
  const loaded = await automationStore.loadRule(ruleId)

  loadingExecutions.value = true
  try {
    await automationStore.loadExecutions(loaded.project_id, { rule_id: ruleId })
  } finally {
    loadingExecutions.value = false
  }
})
</script>
