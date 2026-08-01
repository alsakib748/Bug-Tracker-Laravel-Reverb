<template>
  <AdminLayout>
    <PageBreadcrumb title="Create Project" :pageTitle="currentPageTitle" />
    <!-- <div class="grid grid-cols-1 gap-6 sm:grid-cols-2"> -->
    <div class="space-y-6 sm:space-y-6">
      <ComponentCard title="Project Details">

        <form @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
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
            <Button type="submit" :loading="loading" label="Create Project" icon="pi pi plus"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>

        </form>

      </ComponentCard>
    </div>
    <!-- </div> -->
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router';
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

const currentPageTitle = ref('Create Project')
const router = useRouter();
const projectStore = useProjectStore();
const loading = ref(false);

const form = reactive({
  name: '',
  code: '',
  description: '',
  color: '#3b82f6',
  status: 'active',
});

const errors = reactive({});

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Archived', value: 'archived' },
];

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

  loading.value = true;
  try {
    await projectStore.createProject({ ...form });
    toast.success('Project create successfully');
    router.push('/projects');
  } catch (error) {
    // Handle backend validation errors
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      });
    } else {
      toast.success('Failed to create project: ' + (error.response?.data?.message) || 'Unknown error');
    }
  } finally {
    loading.value = false;
  }

}


</script>
