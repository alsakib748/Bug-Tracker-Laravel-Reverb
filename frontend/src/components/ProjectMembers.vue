<template>

  <div class="mt-6 bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Project Members</h3>
      <!-- Add member button opens a modal/form -->
      <button v-if="canManage" @click="showAddModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
        + Add Member
      </button>
    </div>

    <!-- Member List -->

    <div class="space-y-2">

      <div v-for="member in members" :key="member.id" class="flex justify-between items-center border-b pb-2">
        <div class="flex items-center">
          <img :src="member.avatar || defaultAvatar" @error="onAvatarError" class="w-8 h-8 rounded-full mr-2">
          <span>{{ member.name }}</span>
          <span class="ml-2 text-xs text-gray-500">({{ member.pivot?.role || 'member' }})</span>
          <span v-if="member.id === projectCreatorId"
            class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Creator</span>

          <button v-if="canManage && member.id !== projectCreatorId" @click="confirmRemove(member)"
            class="text-red-600 hover:text-red-800 text-sm ml-2">Remove</button>

        </div>
        <div v-if="!members.length" class="text-gray-500 text-sm">No members yet.</div>
      </div>

      <!-- Add member modal -->

      <div v-if="showAddModal"
        class="fixed inset-0 bg-gray-100 shadow bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96">
          <h4 class="text-lg font-semibold mb-4">Add Member</h4>
          <div class="space-y-4">

            <div>
              <label class="block text-sm font-medium text-gray-700">User</label>
              <Select v-model="newMember.userId" :options="availableUsers" optionLabel="name" optionValue="id"
                placeholder="Search for a user..." filter :filterFields="['name', 'email']" class="w-full mt-1"
                :class="{ 'p-invalid': errors.user }" @change="(val) => console.log('Selected userId:', val)" />
              <small v-if="errors.user" class="text-red-500">{{ errors.user }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Role</label>
              <Select v-model="newMember.role" :options="roleOptions" optionLabel="label" optionValue="value"
                placeholder="Select a role" class="w-full mt-1" :class="{ 'p-invalid': errors.role }"
                @change="clearError('role')" />
              <small v-if="errors.role" class="text-red-500">{{ errors.role }}</small>
            </div>

          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button @click="closeModal" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</button>
            <button @click="addMember" :disabled="!newMember.userId || adding"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disable:opacity-50">
              {{ adding ? 'Adding...' : 'Add Member' }}
            </button>
          </div>

        </div>
      </div>

    </div>

  </div>

</template>

<script setup>

import { ref, onMounted, reactive } from 'vue';
import { useProjectStore } from '@/stores/projects';
import Select from 'primevue/select';
import Swal from 'sweetalert2';

const defaultAvatar = `${import.meta.env.BASE_URL}images/avatar.jpg`;

const onAvatarError = (event) => {
  const img = event.target;
  if (!img || img.dataset.fallbackApplied === 'true') return;
  img.dataset.fallbackApplied = 'true';
  img.src = defaultAvatar;
};

// const defaultAvatar = (member) => {
//   return `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=random&size=32`;
// };


const props = defineProps({
  projectId: {
    type: Number,
    required: true,
  },
  projectCreatorId: {
    type: Number,
    required: true,
  },
  canManage: {
    type: Boolean,
    default: false,
  }
});

const projectStore = useProjectStore();

const members = ref([]);
const availableUsers = ref([]);
const showAddModal = ref(false);
const adding = ref(false);

const newMember = reactive({
  userId: '',
  role: 'developer',
});

const errors = reactive({
  user: null,
  role: null,
});

const roleOptions = [
  { label: 'Manager', value: 'manager' },
  { label: 'Developer', value: 'developer' },
  { label: 'Tester', value: 'tester' },
];


// Load members and available users
const loadMembers = async () => {
  try {
    members.value = await projectStore.fetchMembers(props.projectId);
  } catch (error) {
    console.error('Failed to load members: ', error);
  }
}

const loadAvailableUsers = async () => {
  try {
    availableUsers.value = await projectStore.fetchAvailableUsers(props.projectId);
  } catch (error) {
    console.error('Failed to load available users:', error);
  }
}

// Add member
const addMember = async () => {

  // Reset errors
  errors.user = null;
  errors.role = null;

  if (!newMember.userId) {
    errors.user = 'Please select a user.';
    return;
  }
  if (!newMember.role) {
    errors.role = 'Please select a role.';
    return;
  }
  adding.value = true;
  try {

    await projectStore.addMember(
      props.projectId,
      newMember.userId,
      newMember.role
    );

    await loadMembers();
    await loadAvailableUsers();
    closeModal();
    Swal.fire('Success', 'Member added successfully', 'success');
  } catch (error) {
    console.error('Add member error: ', error);
    console.error('Response data: ', error.response?.data);
    const message = error.response?.data?.message || 'Failed to add member.'
    Swal.fire('Error', message, 'error');
  } finally {
    adding.value = false;
  }
}

// Remove member with confirmation
const confirmRemove = async (member) => {
  const result = await Swal.fire({
    title: 'Remove member?',
    text: `Are you sure you want to remove "${member.name}" from this project`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, remove',
  });
  if (result.isConfirmed) {
    try {
      await projectStore.removeMember(props.projectId, member.id);
      await loadMembers();
      // await loadAvailableUsers();
      Swal.fire('Removed', 'Member removed successfully', 'success');
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to remove member.';
      Swal.fire('Error', message, 'error');
    }
  }
};

const openModal = () => {
  showAddModal.value = true;
  newMember.userId = null;
  newMember.role = 'developer';
  errors.user = null;
  errors.role = null;
  loadAvailableUsers().then(() => {
    console.log('Available users:', availableUsers.value);
  });
}

const closeModal = () => {
  showAddModal.value = false;
  // newMember.value = { userId: '', role: 'developer' };
}

const clearError = (field) => {
  errors[field] = null;
}

onMounted(() => {
  loadMembers();
  if (props.canManage) {
    loadAvailableUsers();
  }
});

</script>
