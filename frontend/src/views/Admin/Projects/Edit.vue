<template>
  <AdminLayout>
    <PageBreadcrumb title="Edit Project" :pageTitle="currentPageTitle" />
    <!-- <div class="grid grid-cols-1 gap-6 sm:grid-cols-2"> -->
    <div class="space-y-6 sm:space-y-6">
      <ComponentCard title="Project Details">
        <div v-if="loading" class="flex justify-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <form v-else @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Project Name <span
                class="text-red-500">*</span></label>

            <InputText id="name" v-model="form.name" type="text" class="w-full mt-1"
              :class="{ 'p-invalid': errors.name }" placeholder="Enter the project name" />
            <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
          </div>

          <div>
            <label for="code" class="block text-sm font-medium text-gray-700">Project Code <span
                class="text-red-500">*</span></label>
            <InputText id="code" v-model="form.code" type="text" class="w-full mt-1"
              :class="{ 'p-invalid': errors.code }" placeholder="Enter the project code" />
            <small v-if="errors.code" class="text-red-500">{{ errors.code }}</small>
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <Textarea id="description" v-model="form.description" rows="4" class="w-full mt-1"
              placeholder="Brief description of the project" />
          </div>

          <div>
            <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
            <div class="flex items-center gap-2 mt-1">
              <input type="color" v-model="form.color" class="w-12 h-12 rounded border cursor-pointer">
              <span class="text-sm text-gray-500">{{ form.color || '#3b82f6' }}</span>
            </div>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <Select id="status" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value"
              class="w-full mt-1" placeholder="Select status" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <router-link to="/projects" class="px-4 py-2 border rounded-md hover:bg-gray-50">
              Cancel
            </router-link>
            <Button type="submit" :loading="loading" label="Update Project" icon="pi pi plus"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>

        </form>

      </ComponentCard>
    </div>
    <!-- </div> -->
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router';
import { useProjectStore } from '@/stores/projects';
import { toast } from 'vue3-toastify'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'

// import DefaultInputs from '@/components/forms/FormElements/DefaultInputs.vue'
// import SelectInput from '@/components/forms/FormElements/SelectInput.vue'
// import InputState from '@/components/forms/FormElements/InputState.vue'
// import TextArea from '@/components/forms/FormElements/TextArea.vue'
// import InputGroup from '@/components/forms/FormElements/InputGroup.vue'
// import Dropzone from '@/components/forms/FormElements/Dropzone.vue'
// import FileInput from '@/components/forms/FormElements/FileInput.vue'
// import CheckboxInput from '@/components/forms/FormElements/CheckboxInput.vue'

// PrimeVue Components

import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';

const currentPageTitle = ref('Edit Project')
const router = useRouter();
const route = useRoute();
const projectStore = useProjectStore();
const loading = ref(false);
const submitting = ref(false);
const project = projectStore.project;

const form = reactive({
  name: '',
  code: '',
  description: '',
  color: '#3b82f6',
  status: 'active',
});

const errors = reactive({});
const projectId = route.params.id;

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Archived', value: 'archived' },
];

const fetchProject = async () => {
  loading.value = true;
  try {
    const project = await projectStore.fetchProject(projectId);
    // Fill form
    form.name = project.name;
    form.code = project.code;
    form.description = project.description || '';
    form.color = project.color || '#3b82f6';
    form.status = project.status || 'active';
  } catch (error) {
    console.error('Failed to fetch project: ', error);
    toast.warning('Product not found!');
    router.push('/projects');
  } finally {
    loading.value = false;
  }
}

const submitForm = async () => {
  //  Reset errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Simple client validation
  if (!form.name.trim()) {
    errors.name = 'Project name is required.';
  }

  if (!form.code.trim()) {
    errors.code = 'Project code is required';
  }

  if (Object.keys(errors).length > 0) {
    return;
  }

  submitting.value = true;
  try {
    await projectStore.updateProject(projectId, { ...form });
    toast.success('Project update successfully');
    router.push('/projects');
  } catch (error) {
    // Handle backend validation errors
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      });
    } else {
      toast.error('Failed to update project: ' + (error.response?.data?.message) || 'Unknown error');
    }
  } finally {
    submitting.value = false;
  }

}

onMounted(fetchProject);

</script>
