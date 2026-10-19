<template>
  <div
    role="link"
    tabindex="0"
    class="group relative flex cursor-pointer items-start gap-3 rounded-xl px-3 py-3 transition duration-150 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:hover:bg-white/[0.04]"
    @click="goToDetail"
    @keydown.enter.prevent="goToDetail"
    @keydown.space.prevent="goToDetail"
  >
    <div class="relative flex-shrink-0">
      <img
        :src="log.user?.avatar || '/images/avatar.jpg'"
        @error="onImgError"
        alt=""
        class="h-10 w-10 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-gray-900"
      />
      <span
        class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white dark:ring-gray-900"
        :class="styles.soft"
      >
        <i :class="styles.icon" class="text-[9px]"></i>
      </span>
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
              :class="styles.soft"
            >
              {{ log.action_label || styles.label }}
            </span>
            <span
              v-if="log.user"
              class="truncate text-xs font-medium text-gray-500 dark:text-gray-400"
            >
              {{ log.user.name }}
            </span>
          </div>

          <p class="mt-1.5 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
            {{ log.description }}
          </p>

          <div v-if="chips.length" class="mt-2 flex flex-wrap items-center gap-2">
            <component
              :is="chip.to ? 'router-link' : 'span'"
              v-for="chip in chips"
              :key="chip.key"
              :to="chip.to"
              class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-600 transition hover:border-brand-300 hover:text-brand-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:border-brand-500/40 dark:hover:text-brand-400"
              @click.stop
            >
              <span
                v-if="chip.dot"
                class="h-1.5 w-1.5 flex-shrink-0 rounded-full"
                :style="{ backgroundColor: chip.dot }"
              ></span>
              <i v-else :class="chip.icon" class="text-[9px]"></i>
              <span class="truncate">{{ chip.label }}</span>
            </component>
          </div>
        </div>

        <div class="flex flex-shrink-0 flex-col items-end gap-2">
          <time
            class="whitespace-nowrap text-xs text-gray-400 dark:text-gray-500"
            :title="absoluteTime"
          >
            {{ relativeTime }}
          </time>
          <i
            class="pi pi-chevron-right text-xs text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500 dark:text-gray-600"
          ></i>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { getActionStyles } from './activityMeta'

const props = defineProps({
  log: { type: Object, required: true },
})

const router = useRouter()

const goToDetail = () => {
  router.push({ name: 'ActivityLogsShow', params: { id: props.log.id } })
}

const styles = computed(() => getActionStyles(props.log.action))

const chips = computed(() => {
  const items = []
  if (props.log.project) {
    items.push({
      key: 'project',
      to: `/projects/${props.log.project.id}`,
      label: props.log.project.name,
      dot: props.log.project.color || null,
      icon: 'pi pi-folder',
    })
  }
  if (props.log.issue) {
    items.push({
      key: 'issue',
      to: `/issues/${props.log.issue.id}`,
      label: props.log.issue.issue_key || `#${props.log.issue.id}`,
      dot: null,
      icon: 'pi pi-circle',
    })
  }
  return items
})

const fallbackAvatar = '/images/avatar.jpg'
const onImgError = (event) => {
  if (event?.target?.src && event.target.src.includes(fallbackAvatar)) return
  if (event?.target) event.target.src = fallbackAvatar
}

const relativeTime = computed(() => timeAgo(props.log.created_at))

const absoluteTime = computed(() =>
  new Date(props.log.created_at).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }),
)

function timeAgo(dateString) {
  const diff = Math.floor((Date.now() - new Date(dateString)) / 1000)
  if (diff < 0) return 'now'
  if (diff < 60) return `${diff}s ago`
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>
