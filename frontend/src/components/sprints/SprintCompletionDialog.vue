<template>
  <Dialog
    :visible="visible"
    modal
    header="Complete Sprint"
    class="w-full max-w-md"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Sprint <span class="font-semibold">{{ sprint?.name }}</span> is being completed. Choose
        what happens to the unfinished issues.
      </p>

      <div class="space-y-2">
        <label class="flex items-center gap-2">
          <RadioButton v-model="carryOver" value="backlog" inputId="carry-backlog" />
          <span class="text-sm">Move to backlog</span>
        </label>
        <label class="flex items-center gap-2">
          <RadioButton v-model="carryOver" value="move_to_sprint" inputId="carry-sprint" />
          <span class="text-sm">Move to another sprint</span>
        </label>
      </div>

      <div v-if="carryOver === 'move_to_sprint'">
        <Select
          v-model="targetSprintId"
          :options="plannedSprints"
          optionLabel="name"
          optionValue="id"
          placeholder="Select a sprint"
          class="w-full"
        />
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        label="Complete Sprint"
        :loading="saving"
        :disabled="carryOver === 'move_to_sprint' && !targetSprintId"
        @click="submit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Select from 'primevue/select'
import RadioButton from 'primevue/radiobutton'

const props = defineProps({
  visible: { type: Boolean, default: false },
  sprint: { type: Object, default: null },
  plannedSprints: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['update:visible', 'complete'])

const carryOver = ref('backlog')
const targetSprintId = ref(null)

watch(
  () => props.visible,
  (open) => {
    if (open) {
      carryOver.value = 'backlog'
      targetSprintId.value = null
    }
  },
)

const submit = () => {
  emit('complete', {
    carry_over: carryOver.value,
    target_sprint_id: carryOver.value === 'move_to_sprint' ? targetSprintId.value : null,
  })
}
</script>
