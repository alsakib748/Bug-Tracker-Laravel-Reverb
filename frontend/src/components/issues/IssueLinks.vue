<template>
  <div>
    <h2 class="text-xl text-gray-700 mb-2">Linked Issues</h2>

    <div v-if="loading" class="flex justify-center py-2">
      <i class="pi pi-spin pi-spinner text-blue-600" style="font-size: 1.25rem"></i>
    </div>

    <div v-else-if="links.length === 0" class="text-sm text-gray-400">
      No linked issues.
    </div>

    <ul v-else class="space-y-2">
      <li v-for="link in links" :key="link.id"
        class="flex items-center justify-between border border-gray-200 rounded px-3 py-2">
        <div class="text-sm text-gray-700 min-w-0">
          <span class="font-medium">{{ link.label }}</span>
          <RouterLink v-if="link.issue" :to="`/issues/${link.issue.id}`"
            class="text-blue-600 hover:underline ml-2">
            #{{ link.issue.id }} {{ link.issue.title }}
          </RouterLink>
        </div>
        <button type="button" class="text-red-500 hover:text-red-700 shrink-0" title="Remove link"
          @click="$emit('remove', link)">
          <i class="pi pi-trash"></i>
        </button>
      </li>
    </ul>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <InputText v-model="targetId" type="number" min="1" placeholder="Issue ID" class="w-32" />
      <Select v-model="linkType" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Type" />
      <Button label="Link issue" :loading="creating" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md"
        @click="add" />
    </div>

    <p v-if="error" class="text-sm text-red-600 mt-2">{{ displayError }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { LINK_TYPE_OPTIONS } from '@/services/issueLinksApi'

const props = defineProps({
  links: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  creating: {
    type: Boolean,
    default: false,
  },
  error: {
    type: [Object, String],
    default: null,
  },
})

const emit = defineEmits(['create', 'remove'])

const targetId = ref('')
const linkType = ref('blocks')
const typeOptions = LINK_TYPE_OPTIONS

const displayError = computed(() => {
  if (!props.error) return ''
  return (
    props.error.response?.data?.message ||
    Object.values(props.error.response?.data?.errors || {}).flat()[0] ||
    'Failed to link issue.'
  )
})

const add = () => {
  if (!targetId.value) return
  emit('create', {
    target_issue_id: Number(targetId.value),
    link_type: linkType.value,
  })
  targetId.value = ''
}
</script>
