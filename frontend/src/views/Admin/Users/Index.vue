<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Users">

        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">All Users</h3>
          <router-link to="/users/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
            + Add User
          </router-link>
        </div>

        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
          <InputText v-model="filters.search" placeholder="Search users..." @input="applyFilters" />
          <Select v-model="filters.role" :options="roleOptions" optionLabel="label" optionValue="value"
            placeholder="Role" clearable @change="applyFilters" />
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="Status" clearable @change="applyFilters" />
        </div>

        <DataTable :value="users" :loading="loading" paginator :rows="pagination.per_page"
          :totalRecords="pagination.total" :first="(pagination.current_page - 1) * pagination.per_page"
          @page="onPageChange" @sort="onSort" sortField="id" :sortOrder="-1" class="p-4">
          <Column field="id" header="#" sortable style="width:60px" />
          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <img
                  :src="data.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&background=random&size=32`"
                  class="w-8 h-8 rounded-full" />
                {{ data.name }}
              </div>
            </template>
          </Column>
          <Column field="email" header="Email" sortable />
          <Column field="role" header="Role" sortable>
            <template #body="{ data }">
              {{ data.role_label }}
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full ${data.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`">
                {{ data.status_label }}
              </span>
            </template>
          </Column>
          <Column field="created_at" header="Joined" sortable>
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>
          <Column header="Actions" style="min-width:150px">
            <template #body="{ data }">
              <router-link :to="`/users/${data.id}/edit`" class="text-blue-600 mr-3">Edit</router-link>
              <button @click="confirmDelete(data)" class="text-red-600">Delete</button>
            </template>
          </Column>
        </DataTable>

        <div v-if="!loading && users.length === 0" class="px-4 pb-4 text-sm text-slate-500">
          No users found.
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { storeToRefs } from 'pinia';
import { useUserStore } from '@/stores/users';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Swal from 'sweetalert2';

const userStore = useUserStore();
const { users, loading, pagination } = storeToRefs(userStore);
const pageTitle = ref('Users');

const filters = reactive({
  search: '',
  role: null,
  status: null,
});

const roleOptions = [
  { label: 'Admin', value: 'admin' },
  { label: 'Developer', value: 'developer' },
  { label: 'Tester', value: 'tester' },
];

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const fetchUsers = async (params = {}) => {
  try {
    const defaultParams = { page: 1, per_page: 15, sort: '-id' };
    const merged = { ...defaultParams, ...params, ...filters };
    await userStore.fetchUsers(merged);
  } catch (error) {
    console.error('Error fetching users:', error);
  } finally {
  }
};

const applyFilters = () => {
  fetchUsers({ page: 1 });
};

const onPageChange = (event) => {
  fetchUsers({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event) => {
  const direction = event.sortOrder === 1 ? '' : '-';
  fetchUsers({ sort: direction + event.sortField });
};

const confirmDelete = async (user) => {
  if (user.role === 'admin' && user.id === 1) {
    Swal.fire('Cannot delete the default admin user.', '', 'error');
    return;
  }
  const result = await Swal.fire({
    title: 'Delete User?',
    text: `Delete "${user.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete',
  });
  if (result.isConfirmed) {
    try {
      await userStore.deleteUser(user.id);
      await fetchUsers();
      Swal.fire('Deleted', 'User deleted.', 'success');
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'Failed to delete.', 'error');
    }
  }
};

onMounted(() => {
  fetchUsers();
});
</script>
