<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="currentPageTitle"
      description="Update the trigger, conditions, and actions for this automation rule."
    />
    <div class="space-y-6">
      <ComponentCard title="Rule builder">
        <AppSkeleton v-if="automationStore.loading" :rows="6" />

        <template v-else-if="automationStore.rule">
          <RuleForm
            :form="form"
            :options="automationStore.options"
            :members="members"
            :labels="labels"
            :errors="errors"
          />

          <div class="mt-6 flex justify-end gap-3 border-t border-line pt-4">
            <AppButton variant="outline" :onClick="goCancel">Cancel</AppButton>
            <AppButton :loading="automationStore.saving" :onClick="submit">
              <i v-if="!automationStore.saving" class="pi pi-check" aria-hidden="true"></i>
              Save changes
            </AppButton>
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
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
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

const goCancel = () => router.push(`/automation/${ruleId}`)

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
