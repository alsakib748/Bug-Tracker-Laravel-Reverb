<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Add New User">
        <form @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Name *</label>
            <InputText v-model="form.name" class="w-full mt-1" :class="{ 'p-invalid': errors.name }" />
            <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Email *</label>
            <InputText v-model="form.email" type="email" class="w-full mt-1" :class="{ 'p-invalid': errors.email }" />
            <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Password *</label>
            <InputText v-model="form.password" type="password" class="w-full mt-1"
              :class="{ 'p-invalid': errors.password }" />
            <small v-if="errors.password" class="text-red-500">{{ errors.password }}</small>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Confirm Password *</label>
            <InputText v-model="form.password_confirmation" type="password" class="w-full mt-1"
              :class="{ 'p-invalid': errors.password_confirmation }" />
            <small v-if="errors.password_confirmation" class="text-red-500">{{ errors.password_confirmation }}</small>
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Role *</label>
            <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value"
              placeholder="Select role" class="w-full mt-1" :class="{ 'p-invalid': errors.role }" />
            <small v-if="errors.role" class="text-red-500">{{ errors.role }}</small>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="Select status" class="w-full mt-1" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <router-link to="/users" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</router-link>
            <Button type="submit" :loading="submitting" label="Create User" icon="pi pi-user-plus"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>
        </form>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/users';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Swal from 'sweetalert2';

const router = useRouter();
const userStore = useUserStore();
const submitting = ref(false);
const pageTitle = ref('Add User');

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'tester',
  status: 'active',
});

const errors = reactive({});

const roleOptions = [
  { label: 'Admin', value: 'admin' },
  { label: 'Developer', value: 'developer' },
  { label: 'Tester', value: 'tester' },
];
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const submitForm = async () => {
  // Reset errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Client-side validation
  if (!form.name) errors.name = 'Name is required.';
  if (!form.email) errors.email = 'Email is required.';
  if (!form.password) errors.password = 'Password is required.';
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match.';
  }
  if (!form.role) errors.role = 'Role is required.';

  if (Object.keys(errors).length > 0) return;

  submitting.value = true;
  try {
    await userStore.createUser({ ...form });
    router.push('/users');
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      });
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to create user.', 'error');
    }
  } finally {
    submitting.value = false;
  }
};
</script>
