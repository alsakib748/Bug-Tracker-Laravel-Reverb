<template>
  <!-- The parent passes a shared reactive `action` object and expects these
       v-models to write into it directly; restructuring to emits would change
       the RuleForm contract. -->
  <!-- eslint-disable vue/no-mutating-props -->
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <!-- Assign issue -->
    <template v-if="action.type === 'assign_issue'">
      <div>
        <label class="block text-xs font-medium text-gray-600">Assignee</label>
        <Select
          v-model="action.config.user_id"
          :options="members"
          optionLabel="name"
          optionValue="id"
          filter
          placeholder="Select a member"
          class="w-full mt-1"
        />
      </div>
    </template>

    <!-- Add / remove label -->
    <template v-else-if="action.type === 'add_label' || action.type === 'remove_label'">
      <div>
        <label class="block text-xs font-medium text-gray-600">Label</label>
        <Select
          v-model="action.config.label_id"
          :options="labels"
          optionLabel="name"
          optionValue="id"
          filter
          placeholder="Select a label"
          class="w-full mt-1"
        />
      </div>
    </template>

    <!-- Change priority -->
    <template v-else-if="action.type === 'change_priority'">
      <div>
        <label class="block text-xs font-medium text-gray-600">Priority</label>
        <Select
          v-model="action.config.priority"
          :options="priorityOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Select a priority"
          class="w-full mt-1"
        />
      </div>
    </template>

    <!-- Add comment -->
    <template v-else-if="action.type === 'add_comment'">
      <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-gray-600">Comment</label>
        <Textarea
          v-model="action.config.body"
          rows="2"
          class="w-full mt-1"
          placeholder="Comment to post"
        />
      </div>
    </template>

    <!-- Notify users -->
    <template v-else-if="action.type === 'notify_users'">
      <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-gray-600">Recipients</label>
        <MultiSelect
          v-model="action.config.recipients"
          :options="members"
          optionLabel="name"
          optionValue="id"
          filter
          display="chip"
          placeholder="Select recipients"
          class="w-full mt-1"
        />
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-gray-600">Message</label>
        <InputText
          v-model="action.config.message"
          class="w-full mt-1"
          placeholder="Notification message"
        />
      </div>
    </template>

    <!-- Set due date -->
    <template v-else-if="action.type === 'set_due_date'">
      <div>
        <label class="block text-xs font-medium text-gray-600">Days from now</label>
        <InputNumber
          v-model="action.config.offset_days"
          :min="0"
          :max="365"
          showButtons
          class="w-full mt-1"
        />
      </div>
    </template>

    <p v-else class="text-sm text-gray-500 sm:col-span-2">Choose an action type.</p>
  </div>
  <!-- eslint-enable vue/no-mutating-props -->
</template>

<script setup>
import { computed } from 'vue'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'

const props = defineProps({
  action: { type: Object, required: true },
  options: { type: Object, default: () => ({ fields: {} }) },
  members: { type: Array, default: () => [] },
  labels: { type: Array, default: () => [] },
})

const priorityOptions = computed(() =>
  (props.options.fields?.priority?.options || ['low', 'medium', 'high', 'critical']).map(
    (value) => ({
      label: value.charAt(0).toUpperCase() + value.slice(1),
      value,
    }),
  ),
)
</script>
