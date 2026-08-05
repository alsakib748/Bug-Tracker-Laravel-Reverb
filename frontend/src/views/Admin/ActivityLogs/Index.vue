<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Activity Logs">
        <ActivityFilter @filter="applyFilters" />
        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <div v-else>
          <ActivityTimeline :logs="logs" :loading="loading" />
          <div class="mt-4 flex justify-center">
            <Paginator v-if="pagination.last_page > 1" :rows="pagination.per_page" :totalRecords="pagination.total"
              :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
          </div>
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useActivityLogStore } from '@/stores/activityLogs';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import ActivityFilter from '@/components/activity/ActivityFilter.vue';
import ActivityTimeline from '@/components/activity/ActivityTimeline.vue';
import Paginator from 'primevue/paginator';

const pageTitle = ref('Activity Logs');
const store = useActivityLogStore();
const logs = ref([]);
const loading = ref(false);
const pagination = ref(store.pagination);
const filters = reactive({});

const loadLogs = async (params = {}) => {
  loading.value = true;
  try {
    await store.fetchLogs(params);
    logs.value = store.logs;
    pagination.value = store.pagination;
  } catch (error) {
    console.error('Error loading logs:', error);
  } finally {
    loading.value = false;
  }
};

const applyFilters = (filterParams) => {
  Object.assign(filters, filterParams);
  loadLogs(filters);
};

const onPageChange = (event) => {
  const page = event.page + 1;
  loadLogs({ ...filters, page });
};

onMounted(() => {
  loadLogs();
});
</script>
