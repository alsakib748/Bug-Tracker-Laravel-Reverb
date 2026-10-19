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
              <img
                :src="currentAvatar"
                alt="Profile avatar"
                @error="onAvatarError"
                class="w-16 h-16 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-800"
              />
              <input type="file" accept="image/*" @change="onFileChange" class="text-sm" />
            </div>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Name *</label>
            <InputText
              v-model="form.name"
              class="w-full mt-1"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Email *</label>
            <InputText
              v-model="form.email"
              type="email"
              class="w-full mt-1"
              :class="{ 'p-invalid': errors.email }"
            />
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
            <Button
              type="submit"
              :loading="submitting"
              label="Update Profile"
              icon="pi pi-save"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md"
            />
          </div>
        </form>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/users'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const authStore = useAuthStore()
const userStore = useUserStore()
const submitting = ref(false)
const pageTitle = ref('My Profile')

const user = computed(() => authStore.user)
const avatarPreview = ref(null)
const defaultAvatar = computed(() => '/images/avatar.jpg')
const currentAvatar = computed(
  () => avatarPreview.value || user.value?.avatar || defaultAvatar.value,
)

const onAvatarError = (event) => {
  const el = event?.target
  if (!el || el.src.includes(defaultAvatar.value)) return
  el.src = defaultAvatar.value
}

const form = reactive({
  name: '',
  email: '',
  password: '',
  avatar: null,
})
const errors = reactive({})
const avatarFile = ref(null)

const loadProfile = () => {
  const u = user.value
  if (!u) return

  // support multiple response shapes: user, { data: user }, { user: { ... } }
  const payload = u.data ?? u.user ?? u

  form.name = payload?.name || ''
  form.email = payload?.email || ''
  // do not overwrite password field
  avatarPreview.value = null
}

const onFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}

const submitForm = async () => {
  Object.keys(errors).forEach((key) => delete errors[key])
  if (!form.name) errors.name = 'Name is required.'
  if (!form.email) errors.email = 'Email is required.'
  if (Object.keys(errors).length > 0) return

  submitting.value = true
  try {
    const data = new FormData()
    data.append('_method', 'PUT')
    data.append('name', form.name)
    data.append('email', form.email)
    if (form.password) {
      data.append('password', form.password)
      data.append('password_confirmation', form.password)
    }

    if (avatarFile.value) {
      data.append('avatar', avatarFile.value)
    }

    const updated = await userStore.updateProfile(data)
    // Refresh auth store from server to ensure full, consistent user object
    try {
      await authStore.getUser()
    } catch (_) {
      // fallback to returned data if refresh fails
      authStore.user = updated
    }
    avatarFile.value = null
    avatarPreview.value = null
    Swal.fire('Success', 'Profile updated successfully.', 'success')
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.keys(backendErrors).forEach((field) => {
        errors[field] = backendErrors[field][0]
        toast.error(errors[field])
      })
    } else {
      Swal.fire('Error', error.response?.data?.message || 'Failed to update profile.', 'error')
    }
  } finally {
    submitting.value = false
  }
}

// Populate form when component mounts and whenever auth user changes
onMounted(loadProfile)
watch(
  user,
  (val) => {
    if (val) loadProfile()
  },
  { immediate: true },
)
</script>
