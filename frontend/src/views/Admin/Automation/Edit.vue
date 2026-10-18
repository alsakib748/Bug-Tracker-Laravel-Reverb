<template>
  <AdminLayout>
    <PageBreadcrumb title="Edit Automation Rule" :pageTitle="currentPageTitle" />
    <div class="space-y-6">
      <ComponentCard title="Rule builder">
        <div v-if="automationStore.loading" class="flex items-center gap-2 text-gray-500">
          <i class="pi pi-spin pi-spinner"></i> Loading rule…
        </div>

        <template v-else-if="automationStore.rule">
          <RuleForm :form="form" :options="automationStore.options" :members="members" :labels="labels"
            :errors="errors" />

          <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
            <router-link :to="`/automation/${ruleId}`" class="px-4 py-2 border rounded-md hover:bg-gray-50">
              Cancel
            </router-link>
            <Button label="Save changes" icon="pi pi-check" :loading="automationStore.saving" @click="submit" />
          </div>
        </template>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/projects'
import { useAutomationStore, blankRule } from '@/stores/automation'
import { fetchProjectLabels } from '@/services/labelsApi'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import RuleForm from '@/components/automation/RuleForm.vue'
import Button from 'primevue/button'
import { toast } from 'vue3-toastify'

const currentPageTitle = ref('Edit Automation Rule')
const route = useRoute()
const router = useRouter()
const projectStore = useProjectStore()
const automationStore = useAutomationStore()

const ruleId = route.params.id
const members = ref([])
const labels = ref([])
const errors = reactive({})
const form = reactive(blankRule())

const loadProjectData = async (projectId) => {
  const [memberList, labelResponse] = await Promise.all([
    projectStore.fetchMembers(projectId),
    fetchProjectLabels(projectId),
  ])

  members.value = memberList
  labels.value = labelResponse.data.data
}

const submit = async () => {
  Object.keys(errors).forEach((key) => delete errors[key])

  if (!form.name.trim()) {
    errors.name = 'Rule name is required.'
  }

  if (!form.actions.length) {
    errors.actions = 'At least one action is required.'
  }

  if (Object.keys(errors).length > 0) return

  try {
    await automationStore.update(ruleId, {
      name: form.name,
      description: form.description,
      trigger_type: form.trigger_type,
      conditions: form.conditions,
      actions: form.actions,
      is_enabled: form.is_enabled,
      run_on_actor_changes: form.run_on_actor_changes,
      cooldown_seconds: form.cooldown_seconds,
    })
    toast.success('Rule updated successfully.')
    router.push(`/automation/${ruleId}`)
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.keys(backendErrors).forEach((field) => {
        errors[field] = backendErrors[field][0]
        toast.error(errors[field])
      })
    } else {
      toast.error(error.response?.data?.message || 'Failed to update the rule.')
    }
  }
}

onMounted(async () => {
  await automationStore.loadOptions()
  const rule = await automationStore.loadRule(ruleId)

  Object.assign(form, {
    project_id: rule.project_id,
    name: rule.name,
    description: rule.description ?? '',
    trigger_type: rule.trigger_type,
    conditions: rule.conditions ?? { match: 'all', rules: [] },
    actions: rule.actions ?? [],
    is_enabled: rule.is_enabled,
    run_on_actor_changes: rule.run_on_actor_changes,
    cooldown_seconds: rule.cooldown_seconds,
  })

  await loadProjectData(rule.project_id)
})
</script>
