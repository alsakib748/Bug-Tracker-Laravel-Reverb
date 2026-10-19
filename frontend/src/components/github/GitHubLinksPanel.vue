<template>
  <div>
    <div v-if="loading" class="flex justify-center py-2">
      <i class="pi pi-spin pi-spinner text-blue-600" style="font-size: 1.25rem"></i>
    </div>

    <div v-else-if="links.length === 0" class="text-sm text-gray-400">
      No GitHub links yet. Link a pull request from GitHub below.
    </div>

    <ul v-else class="space-y-2">
      <li
        v-for="link in links"
        :key="link.id"
        class="flex items-center justify-between border border-gray-200 rounded px-3 py-2"
      >
        <a
          :href="link.external_url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-2 text-sm text-blue-600 hover:underline min-w-0"
        >
          <i :class="link.entity_icon" class="text-gray-500"></i>
          <span class="truncate">
            <template v-if="link.entity_type === 'pull_request'"
              >PR #{{ link.external_number }}</template
            >
            <template v-else-if="link.entity_type === 'commit'"
              >Commit {{ link.external_sha_short }}</template
            >
            <template v-else>{{ link.entity_type_label }}</template>
            · {{ link.repository?.full_name }}
          </span>
          <span
            v-if="link.pull_request"
            class="text-xs px-1.5 py-0.5 rounded shrink-0"
            :class="stateClasses(link.pull_request)"
          >
            {{ stateLabel(link.pull_request) }}
          </span>
          <span
            v-if="link.ci"
            class="text-xs px-1.5 py-0.5 rounded shrink-0"
            :class="ciClasses(link.ci)"
            :title="link.ci.name"
          >
            {{ ciLabel(link.ci) }}
          </span>
          <span class="text-xs text-gray-400 shrink-0">{{ link.link_source_label }}</span>
        </a>
        <button
          v-if="canManage"
          type="button"
          class="text-red-500 hover:text-red-700 shrink-0 ml-2"
          title="Remove link"
          @click="$emit('remove', link)"
        >
          <i class="pi pi-trash"></i>
        </button>
      </li>
    </ul>

    <div v-if="canManage && candidates.length" class="mt-4">
      <Select
        v-model="selectedCandidate"
        :options="candidateOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Select an open pull request"
        class="w-full md:w-96"
        filter
      />
      <Button
        label="Link pull request"
        :loading="linking"
        :disabled="!selectedCandidate"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mt-2"
        @click="linkSelected"
      />
    </div>
    <p v-else-if="canManage && !loading" class="text-xs text-gray-400 mt-2">
      No open pull requests available from this project's linked repositories.
    </p>

    <p v-if="error" class="text-sm text-red-600 mt-2">{{ displayError }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Select from 'primevue/select'
import Button from 'primevue/button'

const props = defineProps({
  links: {
    type: Array,
    default: () => [],
  },
  candidates: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  linking: {
    type: Boolean,
    default: false,
  },
  error: {
    type: [Object, String],
    default: null,
  },
  canManage: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['remove', 'link'])

const selectedCandidate = ref(null)

const candidateOptions = computed(() =>
  props.candidates.map((pr) => ({
    value: pr.id,
    label: `#${pr.number} ${pr.title} (${pr.repository?.full_name})`,
  })),
)

const displayError = computed(() => {
  if (!props.error) return ''
  return (
    props.error.response?.data?.message ||
    Object.values(props.error.response?.data?.errors || {}).flat()[0] ||
    'Failed to link pull request.'
  )
})

const linkSelected = () => {
  if (!selectedCandidate.value) return
  emit('link', selectedCandidate.value)
  selectedCandidate.value = null
}

const stateLabel = (pr) => {
  if (pr.is_merged) return 'Merged'
  if (pr.state === 'closed') return 'Closed'
  if (pr.draft) return 'Draft'
  return 'Open'
}

const stateClasses = (pr) => {
  if (pr.is_merged) return 'bg-purple-100 text-purple-700'
  if (pr.state === 'closed') return 'bg-red-100 text-red-700'
  if (pr.draft) return 'bg-gray-100 text-gray-600'
  return 'bg-green-100 text-green-700'
}

const ciLabel = (ci) => {
  if (ci.status !== 'completed') return 'CI running'
  if (ci.conclusion === 'success') return 'CI passed'
  if (['failure', 'timed_out', 'action_required'].includes(ci.conclusion)) return 'CI failed'
  return 'CI ' + (ci.conclusion || 'unknown')
}

const ciClasses = (ci) => {
  if (ci.status !== 'completed') return 'bg-yellow-100 text-yellow-700'
  if (ci.conclusion === 'success') return 'bg-green-100 text-green-700'
  if (['failure', 'timed_out', 'action_required'].includes(ci.conclusion)) {
    return 'bg-red-100 text-red-700'
  }
  return 'bg-gray-100 text-gray-600'
}
</script>
