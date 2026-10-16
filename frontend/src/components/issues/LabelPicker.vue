<template>
  <Dialog :visible="modelValue" @update:visible="$emit('update:modelValue', $event)" modal header="Manage labels"
    :style="{ width: '30rem' }">
    <div class="space-y-4">
      <MultiSelect v-model="selected" :options="options" optionLabel="name" display="chip" placeholder="Select labels"
        class="w-full" :loading="loading" filter />

      <div v-if="options.length === 0" class="text-sm text-gray-500">
        No labels have been created for this project yet.
      </div>
    </div>

    <template #footer>
      <Button label="Project labels" class="p-button-text" @click="$emit('manage')" />
      <Button label="Cancel" class="p-button-text" :disabled="saving" @click="close" />
      <Button label="Save" :loading="saving" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md"
        @click="save" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  labels: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  saving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'save', 'manage'])

const selected = ref([])

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      const attached = new Set(props.labels.map((label) => label.id))
      selected.value = props.options.filter((label) => attached.has(label.id))
    }
  }
)

const close = () => {
  if (props.saving) return
  emit('update:modelValue', false)
}

const save = () => emit('save', selected.value.map((label) => label.id))
</script>
