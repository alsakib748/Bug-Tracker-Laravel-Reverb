<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle='currentPageTitle' />
    <div class="space-y-5 sm:space-y-6">

      <ComponentCard title="Project Details">

        <div v-if="loading">
          Loading...
        </div>
        <!-- Error state -->
        <div v-else-if="error" class="text-center py-8 text-red-500">
          {{ error }}
        </div>
        <div v-else-if="project" class="grid grid-cols-2 gap-4">
          <div><span class="font-medium">Name: </span>{{ project.name }}</div>
          <div><span class="font-medium">Code: </span>{{ project.code }}</div>
          <div class="col-span-2">
            <span class="font-medium">Description: </span> {{ project.description || 'None' }}
          </div>
          <div>
            <span class="font-medium">Status: </span> {{ project.status_label }}
          </div>
          <div>
            <span class="font-medium">Creator: </span> {{ project.created_by?.name }}
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">No project data available.</div>
      </ComponentCard>
      <!-- Project members component -->
      <ProjectMembers v-if="project" :projectId="project.id" :projectCreatorId="project.created_by?.id"
        :canManage="canManageMembers" />
    </div>
  </AdminLayout>
</template>

<script setup>
import ComponentCard from '@/components/common/ComponentCard.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';

import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useProjectStore } from '@/stores/projects';

// Import the ProjectMembers component
import ProjectMembers from '@/components/ProjectMembers.vue';

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();
const authStore = useAuthStore();

const project = ref(null);
const loading = ref(false);
const error = ref(null);
const currentPageTitle = ref('Project Details');

const canManageMembers = computed(() => {
  if (!project.value) return false;
  return authStore.isAdmin || authStore.user?.id === project.value.created_by;
});

const loadProject = async () => {
  loading.value = true;
  error.value = null;
  try {
    const data = await projectStore.fetchProject(route.params.id);
    project.value = data;
    // console.log(project.value);
  } catch (err) {
    console.error('Failed to load projects: ', err);
    // router.push('/projects');
  } finally {
    loading.value = false;
  }
}

onMounted(loadProject);

</script>
