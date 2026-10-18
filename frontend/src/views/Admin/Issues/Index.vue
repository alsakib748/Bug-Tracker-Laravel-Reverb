<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Issues">

        <div v-if="can('create issues')" class="mb-4 flex justify-end">
          <router-link to="/issues/create" class="bg-blue-600 text-white px-4 py-2 rounded-md">
            + New Issue
          </router-link>
        </div>

        <!-- Filters Bar -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
          <InputText v-model="filters.search" placeholder="Search issues..." @input="applyFilters" />
          <Select v-model="filters.project_id" :options="projects" optionLabel="name" optionValue="id"
            placeholder="Project" clearable @change="applyFilters" />
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="Status" clearable @change="applyFilters" />
          <Select v-model="filters.priority" :options="priorityOptions" optionLabel="label" optionValue="value"
            placeholder="Priority" clearable @change="applyFilters" />
        </div>

        <DataTable :value="issues" :loading="loading" paginator :rows="pagination.per_page"
          :totalRecords="pagination.total" :first="(pagination.current_page - 1) * pagination.per_page"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries" @page="onPageChange"
          @sort="onSort" sortField="id" :sortOrder="-1" class="p-4">
          <Column field="id" header="#" sortable style="width: 60px" />
          <Column field="title" header="Title" sortable>
            <template #body="{ data }">
              <router-link :to="`/issues/${data.id}`" class="text-blue-600 hover:underline">
                {{ data.title }}
              </router-link>
            </template>
          </Column>
          <Column field="project" header="Project" sortable>
            <template #body="{ data }">
              <router-link :to="`/projects/${data.project?.id}`" class="text-blue-700 hover:underline">
                {{ data.project?.name }}
              </router-link>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full bg-${data.status_color}-100 text-${data.status_color}-800`">
                {{ data.status_label }}
              </span>
            </template>
          </Column>
          <Column field="priority" header="Priority" sortable>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full bg-${data.priority_color}-100 text-${data.priority_color}-800`">
                {{ data.priority_label }}
              </span>
            </template>
          </Column>
          <Column field="assignee.name" header="Assignee" sortable />
          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>
          <Column header="Actions" style="min-width: 150px">
            <template #body="{ data }">
              <router-link v-if="can('update issues')" :to="`/issues/${data.id}/edit`"
                class="text-blue-600 mr-3">
                <i class="pi pi-pen-to-square"></i>
              </router-link>
              <button v-if="can('delete issues')" @click="confirmDelete(data)" class="text-red-600">
                <i class="pi pi-trash"></i>
              </button>
              <span v-if="!can('update issues') && !can('delete issues')" class="text-xs text-gray-400">
                View only
              </span>
            </template>
          </Column>
        </DataTable>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

import { ref, onMounted, onUnmounted, reactive } from 'vue';
import { useIssueStore } from '@/stores/issues';
import { useProjectStore } from '@/stores/projects';
import echo from '@/services/echo';
import { onRealtimeReconnect, debounce } from '@/services/realtime';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Swal from 'sweetalert2';

import { storeToRefs } from 'pinia';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const issueStore = useIssueStore();

const { issues, loading, pagination } = storeToRefs(issueStore);

const projectStore = useProjectStore();

// const issues = ref([]);
// const loading = ref(false);
// const pagination = ref(issueStore.pagination);
const projects = ref([]);
const currentPageTitle = ref('Issues');

const filters = reactive({
  search: '',
  project_id: null,
  status: null,
  priority: null,
});

const statusOptions = [
  { label: 'Open', value: 'open' },
  { label: 'Assigned', value: 'assigned' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Code Review', value: 'code_review' },
  { label: 'Testing', value: 'testing' },
  { label: 'Resolved', value: 'resolved' },
  { label: 'Closed', value: 'closed' },
  { label: 'Reopened', value: 'reopened' },
];

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
];

let lastParams = {};

const subscribedProjects = new Set();

const refreshFromRealtime = debounce(() => {
  fetchIssues(lastParams);
});

// The issue list is project-scoped on the backend, so we mirror the realtime
// events for every project currently visible on the page.
const syncProjectSubscriptions = () => {
  const ids = new Set(
    issues.value.map((issue) => issue.project?.id).filter(Boolean)
  );

  ids.forEach((id) => {
    if (subscribedProjects.has(id)) return;
    subscribedProjects.add(id);
    echo
      .private(`project.${id}`)
      .listen('.issue.created', refreshFromRealtime)
      .listen('.issue.updated', refreshFromRealtime)
      .listen('.issue.deleted', refreshFromRealtime);
  });

  Array.from(subscribedProjects).forEach((id) => {
    if (!ids.has(id)) {
      echo.leave(`project.${id}`);
      subscribedProjects.delete(id);
    }
  });
};

const fetchIssues = async (params = {}) => {
  loading.value = true;
  try {
    const defaultParams = {
      page: 1,
      per_page: 10,
      sort: '-id',
    };
    const merged = { ...defaultParams, ...params };
    lastParams = merged;
    const response = await issueStore.fetchIssues(merged);
    issues.value = issueStore.issues;
    pagination.value = issueStore.pagination;
    syncProjectSubscriptions();

    // console.log('Issues from store: ', issues.value);
  } catch (error) {
    console.error('Error fetching issues: ', error);
  } finally {
    loading.value = false;
  }
}

const loadProjects = async () => {
  try {
    await projectStore.fetchAllProjects();
    projects.value = projectStore.projects;
  } catch (error) {
    console.error('Error loading projects: ', error);
  }
};

const applyFilters = () => {
  const params = {
    search: filters.search || undefined,
    project_id: filters.project_id || undefined,
    status: filters.status || undefined,
    priority: filters.priority || undefined,
  };
  fetchIssues(params);
};

const onPageChange = (event) => {
  const params = {
    page: event.page + 1,
    per_page: event.rows,
    sort: '-id',
    ...filters,
  };
  fetchIssues(params);
};

const onSort = (event) => {
  const direction = event.sortOrder === 1 ? '' : '-';
  const params = {
    sort: direction + event.sortField,
    ...filters,
  };
  fetchIssues(params);
};

const confirmDelete = async (issue) => {
  const result = await Swal.fire({
    title: 'Delete Issue?',
    text: `Delete "${issue.title}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete',
  });
  if (result.isConfirmed) {
    try {
      await issueStore.deleteIssue(issue.id);
      await fetchIssues();
      Swal.fire('Deleted', 'Issue deleted', 'success');
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'Failed to delete.', 'error');
    }
  }
}

let removeReconnectListener = null;

onMounted(() => {
  loadProjects();
  fetchIssues();
  removeReconnectListener = onRealtimeReconnect(refreshFromRealtime);
});

onUnmounted(() => {
  if (removeReconnectListener) removeReconnectListener();
  subscribedProjects.forEach((id) => echo.leave(`project.${id}`));
  subscribedProjects.clear();
});

</script>
