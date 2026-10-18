<template>
  <AdminLayout>
    <PageBreadcrumb title="Automation Rule" :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <div v-if="automationStore.loading" class="flex items-center gap-2 text-gray-500">
        <i class="pi pi-spin pi-spinner"></i> Loading rule…
      </div>

      <template v-else-if="rule">
        <ComponentCard title="Rule">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-800">{{ rule.name }}</h3>
              <p class="mt-1 text-sm text-gray-500">{{ rule.description || 'No description.' }}</p>
              <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span :class="rule.is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                  class="rounded-full px-3 py-1 font-medium">
                  {{ rule.is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
                <span class="rounded-full bg-indigo-100 px-3 py-1 font-medium text-indigo-700">
                  {{ triggerLabel(rule.trigger_type) }}
                </span>
                <span class="rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                  v{{ rule.version }}
                </span>
                <span v-if="rule.run_on_actor_changes" class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700">
                  reacts to its own changes
                </span>
              </div>
            </div>

            <div class="flex gap-2">
              <router-link :to="`/automation/${rule.id}/edit`"
                class="px-4 py-2 border rounded-md hover:bg-gray-50">
                Edit
              </router-link>
              <Button :label="rule.is_enabled ? 'Disable' : 'Enable'"
                :icon="rule.is_enabled ? 'pi pi-pause' : 'pi pi-play'" outlined @click="toggle" />
              <Button label="Delete" icon="pi pi-trash" severity="danger" @click="confirmDelete" />
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <h4 class="text-sm font-semibold text-gray-800">Conditions ({{ rule.conditions?.match || 'all' }})</h4>
              <ul v-if="rule.conditions?.rules?.length" class="mt-2 space-y-1 text-sm text-gray-600">
                <li v-for="(condition, index) in rule.conditions.rules" :key="index" class="rounded bg-gray-50 px-3 py-1">
                  <span class="font-medium">{{ humanize(condition.field) }}</span>
                  {{ operatorLabel(condition.operator) }}
                  <span v-if="condition.value !== null && condition.value !== ''" class="font-medium">
                    {{ valueLabel(condition.field, condition.value) }}
                  </span>
                </li>
              </ul>
              <p v-else class="mt-2 text-sm text-gray-500">Runs on every matching trigger.</p>
            </div>

            <div>
              <h4 class="text-sm font-semibold text-gray-800">Actions</h4>
              <ul class="mt-2 space-y-1 text-sm text-gray-600">
                <li v-for="(action, index) in rule.actions" :key="index" class="rounded bg-gray-50 px-3 py-1">
                  {{ actionLabel(action.type) }}
                  <span class="text-gray-400">— {{ describeConfig(action) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </ComponentCard>

        <ComponentCard title="Recent executions">
          <DataTable :value="automationStore.executions" :loading="loadingExecutions" paginator :rows="10"
            responsiveLayout="scroll">
            <Column field="created_at" header="When">
              <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <span :class="statusClass(data.status)" class="rounded-full px-2 py-1 text-xs font-semibold">
                  {{ humanize(data.status) }}
                </span>
              </template>
            </Column>
            <Column field="event_type" header="Trigger">
              <template #body="{ data }">{{ triggerLabel(data.event_type) }}</template>
            </Column>
            <Column header="Result">
              <template #body="{ data }">
                <template v-if="data.actions?.length">
                  <span v-for="action in data.actions" :key="action.id"
                    :class="statusClass(action.status)"
                    class="mr-1 rounded-full px-2 py-0.5 text-[11px] font-medium">
                    {{ actionLabel(data.action_type || action.action_type) }}
                  </span>
                </template>
                <span v-else-if="data.error_code" class="text-xs text-red-600">{{ data.error_code }}</span>
                <span v-else class="text-xs text-gray-400">—</span>
              </template>
            </Column>
          </DataTable>

          <div v-if="!automationStore.executions.length && !loadingExecutions" class="py-4 text-sm text-gray-500">
            This rule has not run yet.
          </div>
        </ComponentCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAutomationStore } from '@/stores/automation'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const currentPageTitle = ref('Automation Rule')
const route = useRoute()
const router = useRouter()
const automationStore = useAutomationStore()

const ruleId = route.params.id
const loadingExecutions = ref(false)

const rule = computed(() => automationStore.rule)

const humanize = (value) =>
  String(value || '')
    .replace(/[._]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const triggerLabel = (value) =>
  automationStore.options.triggers.find((trigger) => trigger.value === value)?.label || humanize(value)

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

const statusClass = (status) =>
  ({
    succeeded: 'bg-green-100 text-green-800',
    partially_failed: 'bg-amber-100 text-amber-800',
    failed: 'bg-red-100 text-red-800',
    skipped: 'bg-gray-100 text-gray-700',
    running: 'bg-blue-100 text-blue-800',
    queued: 'bg-blue-100 text-blue-800',
  })[status] || 'bg-gray-100 text-gray-700'

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '—')

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
