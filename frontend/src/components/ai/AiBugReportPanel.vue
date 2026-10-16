<template>
  <div class="space-y-4">
    <p class="text-sm text-gray-500">
      Let AI review the report, suggest a clearer title/description, a priority and relevant labels.
      Nothing is changed until you choose to apply it.
    </p>

    <!-- Start -->
    <div v-if="!hasSuggestions && !analyzing" class="flex flex-wrap items-center gap-3">
      <Button type="button" label="Analyze report" icon="pi pi-sparkles" :loading="analyzing"
        :disabled="!canAnalyze" @click="run" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded" />
      <small v-if="!projectId" class="text-amber-600">Select a project first.</small>
      <small v-else-if="!context.title" class="text-amber-600">Add a title before analyzing.</small>
    </div>

    <!-- Loading -->
    <div v-if="analyzing" class="flex items-center gap-2 text-gray-600">
      <i class="pi pi-spin pi-spinner"></i>
      <span>Analyzing the report…</span>
    </div>

    <!-- Error -->
    <div v-if="error && !analyzing" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <!-- Results -->
    <template v-if="hasSuggestions && !analyzing">
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="rounded-full bg-indigo-100 px-3 py-1 font-medium text-indigo-700">
          Confidence: {{ suggestions.confidence || 'medium' }}
        </span>
        <span class="rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
          Suggested priority: {{ suggestions.suggested_priority }}
        </span>
      </div>

      <!-- Title -->
      <div class="space-y-1">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
          <Checkbox v-model="useTitle" binary />
          Title
        </label>
        <InputText v-model="reviewedTitle" class="w-full" />
      </div>

      <!-- Description -->
      <div class="space-y-1">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
          <Checkbox v-model="useDescription" binary />
          Description
        </label>
        <Textarea v-model="reviewedDescription" rows="4" class="w-full" />
      </div>

      <!-- Priority -->
      <div class="space-y-1">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
          <Checkbox v-model="usePriority" binary />
          Priority
        </label>
        <p v-if="suggestions.priority_reason" class="text-xs text-gray-500">
          {{ suggestions.priority_reason }}
        </p>
        <Select v-model="reviewedPriority" :options="priorityOptions" optionLabel="label" optionValue="value"
          class="w-full md:w-64" />
      </div>

      <!-- Labels -->
      <div v-if="suggestions.suggested_labels?.length" class="space-y-2">
        <p class="text-sm font-medium text-gray-700">Suggested labels</p>
        <label v-for="label in suggestions.suggested_labels" :key="label.label_id"
          class="flex items-start gap-2 text-sm text-gray-700">
          <Checkbox v-model="selectedLabelIds" :value="label.label_id" />
          <span>
            <span class="font-medium">{{ labelName(label.label_id) }}</span>
            <span v-if="label.reason" class="block text-xs text-gray-500">{{ label.reason }}</span>
          </span>
        </label>
      </div>

      <!-- Missing info -->
      <div v-if="suggestions.missing_information?.length" class="rounded-md bg-amber-50 px-4 py-3 text-sm">
        <p class="font-medium text-amber-800">Missing information</p>
        <ul class="mt-1 list-disc pl-5 text-amber-700">
          <li v-for="(item, index) in suggestions.missing_information" :key="index">
            {{ item.field }}: {{ item.reason }}
          </li>
        </ul>
      </div>

      <!-- Questions -->
      <div v-if="suggestions.questions_for_reporter?.length" class="rounded-md bg-blue-50 px-4 py-3 text-sm">
        <p class="font-medium text-blue-800">Questions for the reporter</p>
        <ul class="mt-1 list-disc pl-5 text-blue-700">
          <li v-for="(question, index) in suggestions.questions_for_reporter" :key="index">{{ question }}</li>
        </ul>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap justify-end gap-3 border-t pt-4">
        <Button type="button" label="Dismiss" :disabled="busy" @click="dismiss"
          class="p-button-text px-4 py-2" />
        <Button type="button" label="Apply selected" icon="pi pi-check" :loading="applying" :disabled="applying"
          @click="applySelected" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import { useAiBugReport } from '@/composables/useAiBugReport'

const props = defineProps({
  projectId: { type: [Number, String], default: null },
  issueId: { type: [Number, String], default: null },
  context: { type: Object, default: () => ({}) },
  availableLabels: { type: Array, default: () => [] },
})

const emit = defineEmits(['apply', 'dismissed'])

const {
  analyzing,
  applying,
  analysisId,
  suggestions,
  error,
  analyze,
  dismiss: dismissRequest,
  reset,
  stopPolling,
} = useAiBugReport()

const useTitle = ref(true)
const useDescription = ref(true)
const usePriority = ref(false)
const reviewedTitle = ref('')
const reviewedDescription = ref('')
const reviewedPriority = ref('medium')
const selectedLabelIds = ref([])

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
]

const canAnalyze = computed(() => !!props.projectId && !!props.context?.title?.trim())
const hasSuggestions = computed(() => !!suggestions.value)

const labelName = (id) => {
  const match = props.availableLabels.find((label) => Number(label.id) === Number(id))
  return match ? match.name : `Label #${id}`
}

watch(suggestions, (value) => {
  if (!value) return

  reviewedTitle.value = value.improved_title || props.context?.title || ''
  reviewedDescription.value = value.improved_description || props.context?.description || ''
  reviewedPriority.value = value.suggested_priority || props.context?.priority || 'medium'
  selectedLabelIds.value = (value.suggested_labels || []).map((label) => label.label_id)

  useTitle.value = !!value.improved_title
  useDescription.value = !!value.improved_description
  usePriority.value = !!value.suggested_priority
})

const run = async () => {
  if (!canAnalyze.value) return

  error.value = null

  await analyze(props.projectId, {
    title: props.context?.title || '',
    description: props.context?.description || '',
    type: props.context?.type || null,
    severity: props.context?.severity || null,
    priority: props.context?.priority || null,
    expected_behavior: props.context?.expected_behavior || null,
    actual_behavior: props.context?.actual_behavior || null,
    reproduction_steps: props.context?.reproduction_steps || [],
    environment: props.context?.environment || null,
    issue_id: props.issueId || null,
  })
}

const applySelected = () => {
  emit('apply', {
    analysisId: analysisId.value,
    accepted: {
      title: useTitle.value,
      description: useDescription.value,
      priority: usePriority.value,
      label_ids: selectedLabelIds.value,
    },
    reviewed: {
      reviewed_title: useTitle.value ? reviewedTitle.value : null,
      reviewed_description: useDescription.value ? reviewedDescription.value : null,
      reviewed_priority: usePriority.value ? reviewedPriority.value : null,
    },
  })
}

const dismiss = async () => {
  await dismissRequest()
  emit('dismissed', analysisId.value)
}

onUnmounted(stopPolling)

defineExpose({ reset })
</script>
