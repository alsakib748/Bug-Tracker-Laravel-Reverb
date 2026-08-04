<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Edit Issue">
        <div v-if="loading" class="flex justify-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <form v-else @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          <!-- Project (disabled, can't change project) -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Project</label>
            <InputText :value="issue?.project?.name" disabled class="w-full mt-1 bg-gray-100" />
          </div>

          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
            <InputText id="title" v-model="form.title" type="text" class="w-full mt-1"
              :class="{ 'p-invalid': errors.title }" required />
            <small v-if="errors.title" class="text-red-500">{{ errors.title }}</small>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <Textarea id="description" v-model="form.description" rows="4" class="w-full mt-1" />
          </div>

          <!-- Priority, Severity, Type -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Priority *</label>
              <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value"
                class="w-full mt-1" :class="{ 'p-invalid': errors.priority }" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Severity *</label>
              <Select v-model="form.severity" :options="severityOptions" optionLabel="label" optionValue="value"
                class="w-full mt-1" :class="{ 'p-invalid': errors.severity }" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Type *</label>
              <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value"
                class="w-full mt-1" :class="{ 'p-invalid': errors.type }" />
            </div>
          </div>

          <!-- Due Date & Estimated Hours -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Due Date</label>
              <DatePicker v-model="form.due_date" dateFormat="yy-mm-dd" placeholder="Select date" class="w-full mt-1"
                :class="{ 'p-invalid': errors.due_date }" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estimated Hours</label>
              <InputNumber v-model="form.estimated_hours" min="0.5" max="999" step="0.5" class="w-full mt-1"
                :class="{ 'p-invalid': errors.estimated_hours }" />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <router-link to="/issues" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</router-link>
            <Button type="submit" :loading="submitting" label="Update Issue" icon="pi pi-save"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>
        </form>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useIssueStore } from '@/stores/issues';
import { useProjectStore } from '@/stores/projects';

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
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify'

const router = useRouter();
const route = useRoute();
const issueStore = useIssueStore();
const projectStore = useProjectStore();

const currentPageTitle = ref('Edit Issue');
const loading = ref(false);
const submitting = ref(false);
const issue = ref(null);
const projectMembers = ref([]);

const form = reactive({
  title: '',
  description: '',
  priority: 'medium',
  severity: 'major',
  type: 'bug',
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

const loadIssue = async () => {
  loading.vale = true;
  try {

    const data = await issueStore.fetchIssue(route.params.id);
    issue.value = data;

    form.title = data.title;
    form.description = data.description;
    form.priority = data.priority;
    form.severity = data.severity;
    form.type = data.type;
    form.due_date = data.due_date ? new Date(data.due_date) : null;
    form.estimated_hours = data.estimated_hours;

  } catch (error) {
    console.error('Failed to load issue: ', error);
    router.push('/issues');
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {

  // Reset errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Validation
  if (!form.title.trim()) errors.title = 'Title is required.';
  if (!form.priority) errors.priority = 'Priority is required.';
  if (!form.severity) errors.severity = 'Severity is required.';
  if (!form.type) errors.type = 'Type is required.';

  if (Object.keys(errors).length > 0) return;

  submitting.value = true;

  try {
    await issueStore.updateIssue(route.params.id, { ...form });
    toast.success('Issue update successfully');
    router.push(`/issues/${route.params.id}`);
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field[0]];
      });
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to update issue.', 'error');
    }
  } finally {
    submitting.value = false;
  }

}

onMounted(loadIssue);

</script>
