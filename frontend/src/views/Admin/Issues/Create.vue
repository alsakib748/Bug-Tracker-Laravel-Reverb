<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Create New Issue">

        <form @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          <!-- Project -->
          <div>
            <label for="project" class="block text-sm font-medium text-gray-700">Project *</label>
            <Select id="project" v-model="form.project_id" :options="projects" optionLabel="name" optionValue="id"
              placeholder="Select a project" class="w-full mt-1" :class="{ 'p-invalid': errors.project_id }" />
            <small v-if="errors.project_id" class="text-red-500">{{ errors.project_id }}</small>
          </div>

          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
            <InputText id="title" v-model="form.title" type="text" class="w-full mt-1"
              :class="{ 'p-invalid': errors.title }" placeholder="Enter issue title" />
            <small v-if="errors.title" class="text-red-500">{{ errors.title }}</small>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <Textarea id="description" v-model="form.description" rows="4" class="w-full mt-1"
              placeholder="Describe the issue" />
          </div>

          <!-- Priority, Severity, Type -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Priority *</label>
              <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value"
                placeholder="Priority" class="w-full mt-1" :class="{ 'p-invalid': errors.priority }" />
              <small v-if="errors.priority" class="text-red-500">{{ errors.priority }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Severity *</label>
              <Select v-model="form.severity" :options="severityOptions" optionLabel="label" optionValue="value"
                placeholder="Severity" class="w-full mt-1" :class="{ 'p-invalid': errors.severity }" />
              <small v-if="errors.severity" class="text-red-500">{{ errors.severity }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Type *</label>
              <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value"
                placeholder="Type" class="w-full mt-1" :class="{ 'p-invalid': errors.type }" />
              <small v-if="errors.type" class="text-red-500">{{ errors.type }}</small>
            </div>
          </div>

          <!-- Assignee -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Assign to</label>
            <Select v-model="form.assigned_to" :options="projectMembers" optionLabel="name" optionValue="id"
              placeholder="Select a developer" class="w-full mt-1" />
          </div>

          <!-- Due Date & Estimated Hours -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Due Date</label>
              <DatePicker v-model="form.due_date" dateFormat="yy-mm-dd" placeholder="Select date" class="w-full mt-1"
                :class="{ 'p-invalid': errors.due_date }" />
              <small v-if="errors.due_date" class="text-red-500">{{ errors.due_date }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estimated Hours</label>
              <InputNumber v-model="form.estimated_hours" min="0.5" max="999" step="0.5" placeholder="e.g., 2.5"
                class="w-full mt-1" :class="{ 'p-invalid': errors.estimated_hours }" />
              <small v-if="errors.estimated_hours" class="text-red-500">{{ errors.estimated_hours }}</small>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <router-link to="/issues" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</router-link>
            <Button type="submit" :loading="submitting" label="Create Issue" icon="pi pi-plus"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>
        </form>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>

import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useIssueStore } from '@/stores/issues';
import { useProjectStore } from '@/stores/projects';
import Swal from 'sweetalert2';

import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';


// PrimeVue
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

const router = useRouter();
const issueStore = useIssueStore();
const projectStore = useProjectStore();

const currentPageTitle = ref('Create Issue');
const submitting = ref(false);
const projects = ref([]);
const projectMembers = ref([]);

const form = reactive({
  project_id: null,
  title: '',
  description: '',
  priority: 'medium',
  severity: 'major',
  type: 'bug',
  assigned_to: null,
  due_date: null,
  estimated_hours: null,
});

const errors = reactive({});

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
];

const severityOptions = [
  { label: 'Minor', value: 'minor' },
  { label: 'Major', value: 'major' },
  { label: 'Critical', value: 'critical' },
  { label: 'Blocker', value: 'blocker' },
];

const typeOptions = [
  { label: 'Bug', value: 'bug' },
  { label: 'Feature', value: 'feature' },
  { label: 'Improvement', value: 'improvement' },
  { label: 'Task', value: 'task' },
];

const loadProjects = async () => {
  try {
    await projectStore.fetchAllProjects();
    projects.value = projectStore.projects;
  } catch (error) {
    console.error('Failed to load projects: ', error);
  }
}

const loadProjectMembers = async (projectId) => {
  console.log('Loading members for project ID:', projectId);
  if (!projectId) {
    projectMembers.value = [];
    return;
  }
  try {
    const members = await projectStore.fetchMembers(projectId);
    console.log('All members:', members);

    const developers = members.filter(m => m.role === 'developer');
    console.log('Developers:', developers);

    projectMembers.value = members;
  } catch (error) {
    console.error('Failed to load project members: ', error);
  }
}

// Watch for project change to reload members
watch(() => form.project_id, (newVal) => {
  // console.log('Project ID changed to:', newVal);
  loadProjectMembers(newVal);
});

const submitForm = async () => {
  // Reset errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Client side validation
  if (!form.project_id) errors.project_id = 'Project is required.';
  if (!form.title.trim()) errors.title = 'Title is required';
  if (!form.priority) errors.priority = 'Priority is required';
  if (!form.severity) errors.severity = 'Severity is required';
  if (!form.type) errors.type = 'Type is required';

  if (Object.keys(errors).length > 0) return;

  submitting.value = true;
  try {
    await issueStore.createIssue({ ...form });
    toast.success('Issue create successfully');
    router.push('/issues');
  } catch (error) {
    // Handle backend validation errors
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      })
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to create issue.', 'error');
    }
  } finally {
    submitting.value = false;
  }

}

onMounted(loadProjects);

</script>
