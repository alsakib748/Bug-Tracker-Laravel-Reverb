<template>
  <Dialog
    :visible="visible"
    modal
    :header="isEdit ? 'Edit Sprint' : 'New Sprint'"
    class="w-full max-w-lg"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="space-y-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
        <InputText v-model="form.name" class="w-full" placeholder="Sprint 1" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Goal</label>
        <Textarea v-model="form.goal" class="w-full" rows="3" autoResize />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Start date</label>
          <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" class="w-full" showIcon />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">End date</label>
          <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" class="w-full" showIcon />
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button :label="isEdit ? 'Save' : 'Create'" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'

const props = defineProps({
  visible: { type: Boolean, default: false },
  sprint: { type: Object, default: null },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['update:visible', 'save'])

const form = reactive({
  name: '',
  goal: '',
  start_date: null,
  end_date: null,
})

const isEdit = computed(() => Boolean(props.sprint?.id))

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    form.name = props.sprint?.name || ''
    form.goal = props.sprint?.goal || ''
    form.start_date = props.sprint?.start_date ? new Date(props.sprint.start_date) : null
    form.end_date = props.sprint?.end_date ? new Date(props.sprint.end_date) : null
  },
)

const toDateString = (value) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

const submit = () => {
  emit('save', {
    name: form.name,
    goal: form.goal || null,
    start_date: toDateString(form.start_date),
    end_date: toDateString(form.end_date),
  })
}
</script>
