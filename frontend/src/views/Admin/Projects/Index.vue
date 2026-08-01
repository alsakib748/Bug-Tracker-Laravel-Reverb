<template>
  <AdminLayout>
    <PageBreadcrumb title="Projects" :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="All Projects">

        <div class="mb-4 flex justify-between">
          <div class="flex gap-4 items-center">
            <i class="pi pi-search"></i>
            <InputText v-model="filters.global.value" placeholder="Search projects..." class="w-64" />
          </div>
          <router-link to="/projects/create" class="bg-blue-600 text-white px-4 py-2 rounded-md">
            + New Project
          </router-link>
        </div>
        <!-- <pre>
          {{ projects }}
        </pre> -->
        <DataTable :value="projects" :loading="loading" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
          v-model:filters="filters" filterDisplay="menu" tableStyle="min-width: 50rem" responsiveLayout="scroll"
          class="p-4">

          <Column field="name" header="Name" sortable filter>
            <template #body="{ data }">
              <router-link :to="`/projects/${data.id}`" class="text-blue-400">
                {{ data.name }}
              </router-link>
            </template>
          </Column>

          <Column field="code" header="Code" sortable filter></Column>

          <Column field="status" header="Status" sortable filter>
            <template #body="{ data }">
              <span
                :class="`px-2 py-1 text-xs font-semibold rounded-full ${data.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`">
                {{ data.status_label }}
              </span>
            </template>
          </Column>

          <Column field="created_by.name" header="Creator" sortable>
          </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>

          <Column header="Actions" style="min-width: 150px">
            <template #body="{ data }">
              <router-link :to="`/projects/${data.id}/edit`" class="text-blue-600 mr-2 hover:underline">
                <i class="pi pi-pen-to-square"></i>
              </router-link>
              <button @click="confirmDelete(data)" class="text-red-600 hover:underline">
                <i class="pi pi-trash"></i>
              </button>
            </template>
          </Column>

        </DataTable>

      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import { useProjectStore } from "@/stores/projects";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import PageBreadcrumb from "@/components/common/PageBreadcrumb.vue";
import AdminLayout from "@/components/layout/AdminLayout.vue";
import ComponentCard from "@/components/common/ComponentCard.vue";
import Swal from 'sweetalert2';

const currentPageTitle = ref("Project List");

const projectStore = useProjectStore();

const projects = ref([]);
const loading = ref(false);

const filters = reactive({
  global: {
    value: null,
    matchMode: 'contains'
  },
});



const fetchAll = async () => {
  loading.value = true;
  try {
    await projectStore.fetchAllProjects();
    projects.value = projectStore.projects;
  } catch (error) {
    console.log('Project List Error', error);
  } finally {
    loading.value = false;
  }
}

const confirmDelete = async (project) => {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete "${project.name}". This cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    try {
      await projectStore.deleteProject(project.id);
      await fetchAll();
      Swal.fire(
        'Deleted!',
        `"${project.name}" has been deleted.`,
        'success'
      );
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to delete project.';
      Swal.fire('Error!', message, 'error');
    }
  }
};

onMounted(fetchAll);

</script>
