<template>
  <AdminLayout>
    <PageBreadcrumb title="Automation" :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Automation Rules">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <Select v-model="projectId" :options="projects" optionLabel="name" optionValue="id"
            placeholder="Select a project" filter class="w-64" @change="loadRules" />
          <router-link v-if="projectId" :to="`/automation/create?project=${projectId}`"
            class="bg-blue-600 text-white px-4 py-2 rounded-md">
            + New Rule
          </router-link>
        </div>

        <div v-if="!projectId" class="text-sm text-gray-500">
          Select a project to see and manage its automation rules.
        </div>

        <DataTable v-else :value="automationStore.rules" :loading="automationStore.loading" paginator :rows="10"
          responsiveLayout="scroll" class="p-2">
          <Column field="name" header="Name">
            <template #body="{ data }">
              <router-link :to="`/automation/${data.id}`" class="text-blue-500 hover:underline">
                {{ data.name }}
              </router-link>
              <div v-if="data.description" class="text-xs text-gray-500">{{ data.description }}</div>
            </template>
          </Column>
          <Column header="Trigger">
            <template #body="{ data }">{{ triggerLabel(data.trigger_type) }}</template>
          </Column>
          <Column header="Conditions">
            <template #body="{ data }">{{ (data.conditions?.rules || []).length }}</template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">{{ (data.actions || []).length }}</template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <span class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="data.is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                {{ data.is_enabled ? 'Enabled' : 'Disabled' }}
              </span>
            </template>
          </Column>
          <Column header="Manage" style="min-width: 170px">
            <template #body="{ data }">
              <router-link :to="`/automation/${data.id}`" class="text-gray-600 mr-2 hover:underline"
                title="View">
                <i class="pi pi-eye"></i>
              </router-link>
              <router-link :to="`/automation/${data.id}/edit`" class="text-blue-600 mr-2 hover:underline"
                title="Edit">
                <i class="pi pi-pen-to-square"></i>
              </router-link>
              <button :title="data.is_enabled ? 'Disable' : 'Enable'" class="text-amber-600 mr-2 hover:underline"
                @click="toggle(data)">
                <i :class="data.is_enabled ? 'pi pi-pause' : 'pi pi-play'"></i>
              </button>
              <button title="Delete" class="text-red-600 hover:underline" @click="confirmDelete(data)">
                <i class="pi pi-trash"></i>
              </button>
            </template>
          </Column>
        </DataTable>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProjectStore } from '@/stores/projects'
import { useAutomationStore } from '@/stores/automation'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const currentPageTitle = ref('Automation Rules')
const route = useRoute()
const projectStore = useProjectStore()
const automationStore = useAutomationStore()

const projects = ref([])
const projectId = ref(null)

const triggerLabel = (value) =>
  automationStore.options.triggers.find((trigger) => trigger.value === value)?.label || value

const loadRules = async () => {
  if (!projectId.value) {
    automationStore.rules = []
    return
  }
  await automationStore.loadRules(projectId.value)
}

const toggle = async (rule) => {
  try {
    await automationStore.toggle(rule)
    toast.success(rule.is_enabled ? 'Rule disabled' : 'Rule enabled')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to update the rule.')
  }
}

const confirmDelete = async (rule) => {
  const result = await Swal.fire({
    title: 'Delete rule?',
    text: `"${rule.name}" will be permanently removed.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'Delete',
  })

  if (!result.isConfirmed) return

  try {
    await automationStore.remove(rule.id)
    toast.success('Rule deleted.')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to delete the rule.')
  }
}

onMounted(async () => {
  await Promise.all([automationStore.loadOptions(), projectStore.fetchAllProjects()])
  projects.value = projectStore.projects

  const queryProject = Number(route.query.project)
  if (queryProject) {
    projectId.value = queryProject
    await loadRules()
  } else if (projects.value.length === 1) {
    projectId.value = projects.value[0].id
    await loadRules()
  }
})
</script>
