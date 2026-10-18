<template>
  <AdminLayout>
    <PageBreadcrumb title="New Automation Rule" :pageTitle="currentPageTitle" />
    <div class="space-y-6">
      <ComponentCard title="Rule builder">
        <div v-if="!projectId" class="max-w-sm">
          <label class="block text-sm font-medium text-gray-700">Project</label>
          <Select v-model="projectId" :options="projects" optionLabel="name" optionValue="id"
            placeholder="Select a project" filter class="w-full mt-1" @change="loadProjectData" />
        </div>

        <template v-else>
          <RuleForm :form="form" :options="automationStore.options" :members="members" :labels="labels"
            :errors="errors" />

          <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
            <router-link :to="`/automation?project=${projectId}`"
              class="px-4 py-2 border rounded-md hover:bg-gray-50">
              Cancel
            </router-link>
            <Button label="Create rule" icon="pi pi-check" :loading="automationStore.saving" @click="submit" />
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
import Select from 'primevue/select'
import Button from 'primevue/button'
import { toast } from 'vue3-toastify'

const currentPageTitle = ref('New Automation Rule')
const route = useRoute()
const router = useRouter()
const projectStore = useProjectStore()
const automationStore = useAutomationStore()

const projects = ref([])
const projectId = ref(Number(route.query.project) || null)
const members = ref([])
const labels = ref([])
const errors = reactive({})
const form = reactive(blankRule(projectId.value))

const loadProjectData = async () => {
  if (!projectId.value) return
  form.project_id = projectId.value

  const [memberList, labelResponse] = await Promise.all([
    projectStore.fetchMembers(projectId.value),
    fetchProjectLabels(projectId.value),
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
    await automationStore.create(projectId.value, { ...form })
    toast.success('Rule created successfully.')
    router.push(`/automation?project=${projectId.value}`)
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.keys(backendErrors).forEach((field) => {
        errors[field] = backendErrors[field][0]
        toast.error(errors[field])
      })
    } else {
      toast.error(error.response?.data?.message || 'Failed to create the rule.')
    }
  }
}

onMounted(async () => {
  await Promise.all([automationStore.loadOptions(), projectStore.fetchAllProjects()])
  projects.value = projectStore.projects

  const queryProject = Number(route.query.project)
  if (queryProject) {
    projectId.value = queryProject
    await loadProjectData()
  } else if (projects.value.length === 1) {
    projectId.value = projects.value[0].id
    await loadProjectData()
  }
})
</script>
