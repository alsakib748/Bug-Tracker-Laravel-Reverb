<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Edit User">
        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <form v-else @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
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
            <label class="block text-sm font-medium text-gray-700">Password (leave blank to keep current)</label>
            <InputText v-model="form.password" type="password" class="w-full mt-1"
              :class="{ 'p-invalid': errors.password }" />
            <small v-if="errors.password" class="text-red-500">{{ errors.password }}</small>
          </div>

          <!-- Confirm Password -->
          <div v-if="form.password">
            <label class="block text-sm font-medium text-gray-700">Confirm Password *</label>
            <InputText v-model="form.password_confirmation" type="password" class="w-full mt-1"
              :class="{ 'p-invalid': errors.password_confirmation }" />
            <small v-if="errors.password_confirmation" class="text-red-500">{{ errors.password_confirmation }}</small>
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value"
              :loading="rolesLoading" placeholder="Select role" class="w-full mt-1">
              <template #option="{ option }">
                <div class="flex items-center justify-between gap-4">
                  <span>{{ option.label }}</span>
                  <span v-if="option.isProtected" class="text-xs text-gray-400">Full access</span>
                  <span v-else-if="option.permissionsCount !== null" class="text-xs text-gray-400">
                    {{ option.permissionsCount }} permissions
                  </span>
                </div>
              </template>
            </Select>
            <small class="text-gray-400">Changing the role replaces the user's assigned permissions.</small>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value"
              class="w-full mt-1" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <router-link to="/users" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</router-link>
            <Button type="submit" :loading="submitting" label="Update User" icon="pi pi-save"
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
import { useUserStore } from '@/stores/users';
import { useRoleOptions } from '@/composables/useRoleOptions';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();
const loading = ref(false);
const submitting = ref(false);
const pageTitle = ref('Edit User');
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  status: '',
});
const errors = reactive({});

const { roleOptions, loading: rolesLoading, load: loadRoles, leastPrivileged } = useRoleOptions();
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const fetchUser = async () => {
  loading.value = true;
  try {
    const user = await userStore.fetchUser(route.params.id);
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.status = user.status;
  } catch (error) {
    console.error('Error fetching user:', error);
    router.push('/users');
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  Object.keys(errors).forEach(key => delete errors[key]);
  if (!form.name) errors.name = 'Name is required.';
  if (!form.email) errors.email = 'Email is required.';
  if (form.password && form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters.';
  }
  if (form.password && form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match.';
  }
  if (Object.keys(errors).length > 0) return;

  const payload = {
    name: form.name,
    email: form.email,
    role: form.role,
    status: form.status,
  };

  // Only send the password pair when the admin is actually changing it;
  // the backend requires a matching `password_confirmation` otherwise.
  if (form.password) {
    payload.password = form.password;
    payload.password_confirmation = form.password_confirmation;
  }

  submitting.value = true;
  try {
    await userStore.updateUser(route.params.id, payload);
    router.push('/users');
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      });
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to update user.', 'error');
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(async () => {
  await loadRoles();
  await fetchUser();
  if (!roleOptions.value.some((option) => option.value === form.role)) {
    form.role = leastPrivileged() || '';
  }
});
</script>
