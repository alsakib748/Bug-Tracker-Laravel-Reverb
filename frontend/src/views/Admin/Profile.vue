<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="My Profile">
        <form @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          <!-- Avatar -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Avatar</label>
            <div class="flex items-center gap-4">
              <img :src="currentAvatar" class="w-16 h-16 rounded-full object-cover" />
              <input type="file" @change="onFileChange" accept="image/*" class="text-sm" />
            </div>
          </div>

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

          <!-- Password (optional) -->
          <div>
            <label class="block text-sm font-medium text-gray-700">New Password (optional)</label>
            <InputText v-model="form.password" type="password" class="w-full mt-1" />
            <small class="text-gray-500">Leave blank to keep current password.</small>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button type="submit" :loading="submitting" label="Update Profile" icon="pi pi-save"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md" />
          </div>
        </form>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/users';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const userStore = useUserStore();
const submitting = ref(false);
const pageTitle = ref('My Profile');

const user = computed(() => authStore.user);
const avatarPreview = ref(null);
const defaultAvatar = computed(() => `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.name || 'User')}&background=random&size=64`);
const currentAvatar = computed(() => avatarPreview.value || user.value?.avatar || defaultAvatar.value);

const form = reactive({
  name: '',
  email: '',
  password: '',
  avatar: null,
});
const errors = reactive({});
const avatarFile = ref(null);

const loadProfile = () => {
  if (user.value) {
    form.name = user.value.name;
    form.email = user.value.email;
    avatarPreview.value = null;
  }
};

const onFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    avatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
};

const submitForm = async () => {
  Object.keys(errors).forEach(key => delete errors[key]);
  if (!form.name) errors.name = 'Name is required.';
  if (!form.email) errors.email = 'Email is required.';
  if (Object.keys(errors).length > 0) return;

  submitting.value = true;
  try {
    const data = new FormData();
    data.append('_method', 'PUT');
    data.append('name', form.name);
    data.append('email', form.email);
    if (form.password) {
      data.append('password', form.password);
      data.append('password_confirmation', form.password);
    }
    if (avatarFile.value) {
      data.append('avatar', avatarFile.value);
    }

    const updated = await userStore.updateProfile(data);
    // Update auth store
    authStore.user = updated;
    avatarFile.value = null;
    avatarPreview.value = null;
    Swal.fire('Success', 'Profile updated successfully.', 'success');
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach(field => {
        errors[field] = backendErrors[field][0];
      });
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to update profile.', 'error');
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(loadProfile);
</script>
