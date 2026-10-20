<template>
  <ComponentCard
    title="Created vs resolved"
    desc="Issues created and resolved per bucket for the selected period."
    class="h-full"
  >
    <div v-if="isEmpty" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
      No issue activity in this period.
    </div>

    <div
      v-else
      ref="host"
      class="relative w-full text-gray-400 dark:text-gray-500"
      @mousemove="onMove"
      @mouseleave="hoverIndex = -1"
    >
      <div class="mb-2 flex items-center justify-end gap-4 text-xs">
        <span class="inline-flex items-center gap-1.5">
          <span class="h-2.5 w-2.5 rounded-full" style="background: #465fff" /> Created
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span class="h-2.5 w-2.5 rounded-full" style="background: #12b76a" /> Resolved
        </span>
      </div>

      <svg
        :width="width"
        :height="height"
        :viewBox="`0 0 ${width} ${height}`"
        class="block max-w-full"
        role="img"
        aria-label="Issues created and resolved over the selected period"
      >
        <defs>
          <linearGradient id="trend-created-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#465fff" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#465fff" stop-opacity="0" />
          </linearGradient>
          <linearGradient id="trend-resolved-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#12b76a" stop-opacity="0.24" />
            <stop offset="100%" stop-color="#12b76a" stop-opacity="0" />
          </linearGradient>
        </defs>

        <g>
          <line
            v-for="tick in yTicks"
            :key="`grid-${tick.value}`"
            :x1="padLeft"
            :x2="width - padRight"
            :y1="tick.y"
            :y2="tick.y"
            stroke="rgba(148, 163, 184, 0.18)"
            stroke-width="1"
            :stroke-dasharray="tick.value === 0 ? '0' : '4 4'"
          />
          <text
            v-for="tick in yTicks"
            :key="`label-${tick.value}`"
            :x="padLeft - 8"
            :y="tick.y + 4"
            text-anchor="end"
            fill="currentColor"
            font-size="11"
          >
            {{ tick.value }}
          </text>
        </g>

        <path :d="createdArea" fill="url(#trend-created-fill)" />
        <path :d="resolvedArea" fill="url(#trend-resolved-fill)" />
        <path
          :d="createdLine"
          fill="none"
          stroke="#465fff"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          :d="resolvedLine"
          fill="none"
          stroke="#12b76a"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <text
          v-for="label in xLabels"
          :key="`x-${label.index}`"
          :x="label.x"
          :y="height - 8"
          text-anchor="middle"
          fill="currentColor"
          font-size="11"
        >
          {{ label.label }}
        </text>

        <g v-if="hoverIndex >= 0">
          <line
            :x1="xFor(hoverIndex)"
            :x2="xFor(hoverIndex)"
            :y1="padTop"
            :y2="height - padBottom"
            stroke="rgba(148, 163, 184, 0.5)"
            stroke-width="1"
          />
          <circle
            :cx="xFor(hoverIndex)"
            :cy="yFor(created[hoverIndex])"
            r="3.5"
            fill="#465fff"
            stroke="#fff"
            stroke-width="2"
          />
          <circle
            :cx="xFor(hoverIndex)"
            :cy="yFor(resolved[hoverIndex])"
            r="3.5"
            fill="#12b76a"
            stroke="#fff"
            stroke-width="2"
          />
        </g>
      </svg>

      <div
        v-if="hoverIndex >= 0"
        class="pointer-events-none absolute top-6 z-10 -translate-x-1/2 rounded-md bg-gray-900 px-3 py-2 text-xs text-white shadow-lg"
        :style="tooltipStyle"
      >
        <div class="mb-1 font-medium">{{ buckets[hoverIndex] }}</div>
        <div class="flex items-center gap-1.5">
          <span class="h-2 w-2 rounded-full" style="background: #465fff" />
          Created: {{ created[hoverIndex] }}
        </div>
        <div class="flex items-center gap-1.5">
          <span class="h-2 w-2 rounded-full" style="background: #12b76a" />
          Resolved: {{ resolved[hoverIndex] }}
        </div>
      </div>
    </div>
  </ComponentCard>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import ComponentCard from '@/components/common/ComponentCard.vue'

const props = defineProps({
  series: {
    type: Object,
    default: () => ({ buckets: [], created: [], resolved: [] }),
  },
})

const height = 320
const padLeft = 36
const padRight = 12
const padTop = 12
const padBottom = 26

const host = ref(null)
const width = ref(640)

const buckets = computed(() => props.series?.buckets ?? [])
const created = computed(() => props.series?.created ?? [])
const resolved = computed(() => props.series?.resolved ?? [])
const isEmpty = computed(() => !buckets.value.length)

const plotWidth = computed(() => Math.max(1, width.value - padLeft - padRight))
const plotHeight = computed(() => Math.max(1, height - padTop - padBottom))

// Pick a friendly gridline step (1/2/5/10 × 10ⁿ) so every tick is a whole
// number, then round the axis maximum up to the top tick.
const niceStep = (value) => {
  const pow = Math.pow(10, Math.floor(Math.log10(value)))
  const n = value / pow
  const step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10

  return Math.max(1, step * pow)
}

const axis = computed(() => {
  const max = Math.max(1, ...created.value, ...resolved.value)
  const step = niceStep(max / 4)
  const top = Math.ceil(max / step) * step

  const ticks = []
  for (let value = 0; value <= top + 1e-9; value += step) ticks.push(Math.round(value))

  return { top, ticks }
})

const maxValue = computed(() => axis.value.top)

const xFor = (index) => {
  const count = buckets.value.length
  if (count <= 1) return padLeft + plotWidth.value / 2
  return padLeft + (index / (count - 1)) * plotWidth.value
}

const yFor = (value) =>
  padTop + plotHeight.value - (Number(value) / maxValue.value) * plotHeight.value

const yTicks = computed(() =>
  axis.value.ticks.map((value) => ({ value, y: yFor(value) })),
)

const linePath = (values) =>
  values
    .map((value, index) => `${index === 0 ? 'M' : 'L'}${xFor(index).toFixed(2)},${yFor(value).toFixed(2)}`)
    .join(' ')

const areaPath = (values) => {
  if (!values.length) return ''

  const base = (padTop + plotHeight.value).toFixed(2)
  const lastX = xFor(values.length - 1).toFixed(2)
  const firstX = xFor(0).toFixed(2)

  return `${linePath(values)} L${lastX},${base} L${firstX},${base} Z`
}

const createdLine = computed(() => linePath(created.value))
const resolvedLine = computed(() => linePath(resolved.value))
const createdArea = computed(() => areaPath(created.value))
const resolvedArea = computed(() => areaPath(resolved.value))

const shortLabel = (label) => {
  const value = String(label ?? '')
  const iso = value.match(/^(\d{4})-(\d{2})-(\d{2})/)
  return iso ? `${iso[2]}-${iso[3]}` : value
}

const xLabels = computed(() => {
  const count = buckets.value.length
  if (!count) return []

  const maxLabels = Math.max(1, Math.floor(plotWidth.value / 80))
  const step = Math.ceil(count / maxLabels)
  const labels = []

  for (let index = 0; index < count; index += step) {
    labels.push({ index, x: xFor(index), label: shortLabel(buckets.value[index]) })
  }

  return labels
})

const hoverIndex = ref(-1)

const onMove = (event) => {
  const count = buckets.value.length
  if (!count) return

  if (count === 1) {
    hoverIndex.value = 0
    return
  }

  const rect = event.currentTarget.getBoundingClientRect()
  const ratio = (event.clientX - rect.left - padLeft) / plotWidth.value
  const index = Math.round(ratio * (count - 1))

  hoverIndex.value = Math.min(count - 1, Math.max(0, index))
}

const tooltipStyle = computed(() => {
  const index = hoverIndex.value
  if (index < 0) return {}

  const x = Math.min(Math.max(xFor(index), 64), width.value - 64)

  return { left: `${x}px` }
})

let observer = null
let frame = 0

const measure = () => {
  if (!host.value) return

  // Reading clientWidth synchronously inside a ResizeObserver callback forces
  // a style/layout flush (a "forced reflow"). Deferring to the next animation
  // frame lets the pending layout settle first, so the read is cheap.
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    if (host.value) width.value = Math.max(240, host.value.clientWidth)
  })
}

onMounted(() => {
  measure()

  if (typeof ResizeObserver !== 'undefined' && host.value) {
    observer = new ResizeObserver(measure)
    observer.observe(host.value)
  } else if (typeof window !== 'undefined') {
    window.addEventListener('resize', measure)
  }
})

onBeforeUnmount(() => {
  cancelAnimationFrame(frame)

  if (observer) {
    observer.disconnect()
    observer = null
  } else if (typeof window !== 'undefined') {
    window.removeEventListener('resize', measure)
  }
})
</script>
