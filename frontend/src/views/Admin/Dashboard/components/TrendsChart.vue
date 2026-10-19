<template>
  <ComponentCard
    title="Created vs resolved"
    desc="Issues created and resolved per bucket for the selected period."
    class="h-full"
  >
    <div v-if="isEmpty" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
      No issue activity in this period.
    </div>

    <VueApexCharts
      v-else
      type="area"
      height="320"
      :options="chartOptions"
      :series="chartSeries"
    />
  </ComponentCard>
</template>

<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import ComponentCard from '@/components/common/ComponentCard.vue'

const props = defineProps({
  series: {
    type: Object,
    default: () => ({ buckets: [], created: [], resolved: [] }),
  },
})

const isEmpty = computed(() => !props.series?.buckets?.length)

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'Inter, ui-sans-serif, system-ui',
  },
  colors: ['#465fff', '#12b76a'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0, stops: [0, 90, 100] },
  },
  grid: {
    borderColor: 'rgba(148, 163, 184, 0.16)',
    strokeDashArray: 4,
    padding: { left: 6, right: 10 },
  },
  markers: { size: 0, strokeWidth: 2, hover: { size: 6 } },
  tooltip: { theme: 'dark' },
  xaxis: {
    categories: props.series?.buckets ?? [],
    labels: {
      rotate: -45,
      rotateAlways: (props.series?.buckets?.length ?? 0) > 12,
      style: { fontSize: '11px' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { min: 0, forceNiceScale: true },
  legend: { position: 'top', horizontalAlign: 'right' },
}))

const chartSeries = computed(() => [
  { name: 'Created', data: props.series?.created ?? [] },
  { name: 'Resolved', data: props.series?.resolved ?? [] },
])
</script>
