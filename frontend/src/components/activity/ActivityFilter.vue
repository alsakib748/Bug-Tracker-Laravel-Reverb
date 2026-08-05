<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <InputText v-model="filters.search" placeholder="Search logs..." @input="apply" />
    <Select v-model="filters.action" :options="actionOptions" optionLabel="label" optionValue="value"
      placeholder="Action" clearable @change="apply" />
    <Select v-model="filters.user_id" :options="users" optionLabel="name" optionValue="id" placeholder="User" clearable
      @change="apply" />
    <DatePicker v-model="filters.date" selectionMode="range" placeholder="Date range" class="w-full"
      @update:modelValue="apply" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
// import { useUserStore } from '@/stores/users';

const emit = defineEmits(['filter']);

const filters = reactive({
  search: '',
  action: null,
  user_id: null,
  date: null,
});

const users = ref([]);
const actionOptions = [
  { label: 'Project Created', value: 'project_created' },
  { label: 'Project Updated', value: 'project_updated' },
  { label: 'Member Added', value: 'member_added' },
  { label: 'Member Removed', value: 'member_removed' },
  { label: 'Issue Created', value: 'issue_created' },
  { label: 'Issue Assigned', value: 'issue_assigned' },
  { label: 'Issue Status Changed', value: 'issue_status_changed' },
  { label: 'Issue Closed', value: 'issue_closed' },
  { label: 'Issue Reopened', value: 'issue_reopened' },
  { label: 'Comment Added', value: 'comment_added' },
  { label: 'Comment Updated', value: 'comment_updated' },
  { label: 'Comment Deleted', value: 'comment_deleted' },
];

const apply = () => {
  const params = {
    search: filters.search || undefined,
    action: filters.action || undefined,
    user_id: filters.user_id || undefined,
  };
  if (filters.date && filters.date.length === 2) {
    params.from = filters.date[0]?.toISOString().split('T')[0];
    params.to = filters.date[1]?.toISOString().split('T')[0];
  }
  emit('filter', params);
};

// Load users for dropdown
const loadUsers = async () => {
  // If you have a user store, fetch users
};

onMounted(loadUsers);
</script>
