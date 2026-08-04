<!-- <template>
  <admin-layout>
    <pre>
      Md. Al Sakib Ayon
    </pre>
    <div class="grid grid-cols-12 gap-4 md:gap-6">
      <div class="col-span-12 space-y-6 xl:col-span-7">
        <ecommerce-metrics />
        <monthly-target />
      </div>
      <div class="col-span-12 xl:col-span-5">
        <monthly-sale />
      </div>

      <div class="col-span-12">
        <statistics-chart />
      </div>

      <div class="col-span-12 xl:col-span-5">
        <customer-demographic />
      </div>

      <div class="col-span-12 xl:col-span-7">
        <recent-orders />
      </div>
    </div>
  </admin-layout>
</template>

<script>
import AdminLayout from '../components/layout/AdminLayout.vue'
import EcommerceMetrics from '../components/ecommerce/EcommerceMetrics.vue'
import MonthlyTarget from '../components/ecommerce/MonthlySale.vue'
import MonthlySale from '../components/ecommerce/MonthlyTarget.vue'
import CustomerDemographic from '../components/ecommerce/CustomerDemographic.vue'
import StatisticsChart from '../components/ecommerce/StatisticsChart.vue'
import RecentOrders from '../components/ecommerce/RecentOrders.vue'
export default {
  components: {
    AdminLayout,
    EcommerceMetrics,
    MonthlyTarget,
    MonthlySale,
    CustomerDemographic,
    StatisticsChart,
    RecentOrders,
  },
  name: 'Ecommerce',
}
</script> -->

<template>
  <AdminLayout>
    <PageBreadCrumb :pageTitle="pageTitle" />
    <div class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500">Total Projects</h3>
          <p class="text-3xl font-bold text-gray-800">{{ stats.total_projects }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500">Total Issues</h3>
          <p class="text-3xl font-bold text-gray-800">{{ stats.total_issues }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500">Open Issues</h3>
          <p class="text-3xl font-bold text-orange-600">{{ stats.open_issues }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-500">Critical Issues</h3>
          <p class="text-3xl font-bold text-red-600">{{ stats.critical_issues }}</p>
        </div>
      </div>

      <!-- Secondary Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
          <span class="text-sm text-gray-500">In Progress</span>
          <p class="text-2xl font-bold text-blue-600">{{ stats.in_progress_issues }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <span class="text-sm text-gray-500">Closed</span>
          <p class="text-2xl font-bold text-green-600">{{ stats.closed_issues }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <span class="text-sm text-gray-500">Completion Rate</span>
          <p class="text-2xl font-bold text-gray-800">
            {{ stats.total_issues > 0 ? Math.round((stats.closed_issues / stats.total_issues) * 100) : 0 }}%
          </p>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
        <div v-if="stats.recent_activity.length === 0" class="text-gray-500 text-center py-4">
          No recent activity.
        </div>
        <ul v-else class="divide-y divide-gray-200">
          <li v-for="(activity, index) in stats.recent_activity" :key="index" class="py-3">
            <div class="flex items-start">
              <span class="text-sm text-gray-600">{{ activity.description }}</span>
              <span class="ml-auto text-xs text-gray-400 whitespace-nowrap">
                {{ formatDate(activity.created_at) }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              by {{ activity.user }}
            </div>
          </li>
        </ul>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>

import { ref, onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadCrumb from '@/components/common/PageBreadcrumb.vue';

const dashboardStore = useDashboardStore();
const stats = ref(dashboardStore.stats);
const pageTitle = ref('Dashboard');

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const fetchDashboard = async () => {
  try {
    await dashboardStore.fetchDashboard();
    stats.value = dashboardStore.stats;
  } catch (error) {
    console.error('Error loading dashboard:', error);
  }
};

onMounted(fetchDashboard);


</script>
