<template>
  <!-- The parent owns a shared reactive `form` object; all v-models and the
       helpers below write into it by design (Create/Edit pass the same ref).
       Restructuring to emits would change the form contract. -->
  <!-- eslint-disable vue/no-mutating-props -->
  <div class="space-y-8">
    <!-- Basics -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Rule name</label>
        <InputText
          v-model="form.name"
          class="w-full mt-1"
          placeholder="e.g. Auto-assign critical bugs"
        />
        <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <Textarea
          v-model="form.description"
          rows="2"
          class="w-full mt-1"
          placeholder="What does this rule do?"
        />
      </div>
    </div>

    <!-- Trigger -->
    <div>
      <h4 class="mb-2 text-sm font-semibold text-gray-800">When this happens</h4>
      <Select
        v-model="form.trigger_type"
        :options="options.triggers"
        optionLabel="label"
        optionValue="value"
        placeholder="Select a trigger"
        class="w-full sm:w-96"
        @change="onTriggerChange"
      />
      <small v-if="errors.trigger_type" class="text-red-500">{{ errors.trigger_type }}</small>
    </div>

    <!-- Conditions -->
    <div>
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-gray-800">Only if</h4>
        <div class="flex items-center gap-3">
          <Select
            v-model="form.conditions.match"
            :options="matchOptions"
            optionLabel="label"
            optionValue="value"
            class="w-40"
          />
          <Button
            type="button"
            label="Add condition"
            icon="pi pi-plus"
            size="small"
            outlined
            @click="addCondition"
          />
        </div>
      </div>

      <div v-if="!form.conditions.rules.length" class="mt-2 text-sm text-gray-500">
        No conditions — this rule runs every time the trigger fires.
      </div>

      <div
        v-for="(condition, index) in form.conditions.rules"
        :key="index"
        class="mt-3 grid grid-cols-1 items-end gap-3 rounded-md border border-gray-200 p-3 sm:grid-cols-12"
      >
        <div class="sm:col-span-4">
          <label class="block text-xs font-medium text-gray-600">Field</label>
          <Select
            v-model="condition.field"
            :options="fieldOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full mt-1"
            @change="onConditionFieldChange(condition)"
          />
        </div>
        <div class="sm:col-span-3">
          <label class="block text-xs font-medium text-gray-600">Operator</label>
          <Select
            v-model="condition.operator"
            :options="options.operators"
            optionLabel="label"
            optionValue="value"
            class="w-full mt-1"
          />
        </div>
        <div v-if="operatorRequiresValue(condition.operator)" class="sm:col-span-4">
          <label class="block text-xs font-medium text-gray-600">Value</label>
          <Select
            v-if="fieldType(condition.field) === 'enum'"
            v-model="condition.value"
            :options="fieldOptionsFor(condition.field)"
            optionLabel="label"
            optionValue="value"
            :showClear="true"
            class="w-full mt-1"
          />
          <Select
            v-else-if="fieldType(condition.field) === 'user'"
            v-model="condition.value"
            :options="members"
            optionLabel="name"
            optionValue="id"
            filter
            :showClear="true"
            class="w-full mt-1"
          />
          <InputNumber
            v-else-if="fieldType(condition.field) === 'number'"
            v-model="condition.value"
            class="w-full mt-1"
          />
          <InputText v-else v-model="condition.value" class="w-full mt-1" placeholder="Value" />
        </div>
        <div class="sm:col-span-1 sm:col-start-12 flex justify-end">
          <Button
            type="button"
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            @click="removeCondition(index)"
          />
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div>
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-gray-800">Then do this</h4>
        <Button
          type="button"
          label="Add action"
          icon="pi pi-plus"
          size="small"
          outlined
          @click="addAction"
        />
      </div>

      <div v-if="!form.actions.length" class="mt-2 text-sm text-gray-500">
        At least one action is required.
      </div>
      <small v-if="errors.actions" class="text-red-500">{{ errors.actions }}</small>

      <div
        v-for="(action, index) in form.actions"
        :key="index"
        class="mt-3 rounded-md border border-gray-200 p-3"
      >
        <div class="mb-3 flex items-end justify-between gap-3">
          <div class="w-full sm:w-72">
            <label class="block text-xs font-medium text-gray-600">Action</label>
            <Select
              v-model="action.type"
              :options="options.actions"
              optionLabel="label"
              optionValue="value"
              class="w-full mt-1"
              @change="onActionTypeChange(action)"
            />
          </div>
          <Button
            type="button"
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            @click="removeAction(index)"
          />
        </div>
        <ActionConfigEditor
          :action="action"
          :options="options"
          :members="members"
          :labels="labels"
        />
      </div>
    </div>

    <!-- Behaviour -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="flex items-center gap-2">
        <ToggleSwitch v-model="form.is_enabled" />
        <label class="text-sm text-gray-700">Enabled</label>
      </div>
      <div class="flex items-center gap-2">
        <ToggleSwitch v-model="form.run_on_actor_changes" />
        <label class="text-sm text-gray-700">Also run on changes made by this rule</label>
      </div>
      <div>
        <label class="block text-sm text-gray-700">Cooldown (seconds)</label>
        <InputNumber
          v-model="form.cooldown_seconds"
          class="w-full mt-1"
          placeholder="No cooldown"
        />
      </div>
    </div>
  </div>
  <!-- eslint-enable vue/no-mutating-props -->
</template>

<script setup>
/* eslint-disable vue/no-mutating-props -- parent-owned reactive form object */
import { computed } from 'vue'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import ToggleSwitch from 'primevue/toggleswitch'
import ActionConfigEditor from '@/components/automation/ActionConfigEditor.vue'

const props = defineProps({
  form: { type: Object, required: true },
  options: {
    type: Object,
    default: () => ({ triggers: [], operators: [], actions: [], fields: {} }),
  },
  members: { type: Array, default: () => [] },
  labels: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
})

const matchOptions = [
  { label: 'Match all', value: 'all' },
  { label: 'Match any', value: 'any' },
]

const humanize = (value) =>
  String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const selectedTrigger = computed(() =>
  (props.options.triggers || []).find((trigger) => trigger.value === props.form.trigger_type),
)

const allowedFieldKeys = computed(
  () => selectedTrigger.value?.fields || Object.keys(props.options.fields || {}),
)

const fieldOptions = computed(() =>
  allowedFieldKeys.value.map((key) => ({ label: humanize(key), value: key })),
)

const fieldType = (field) => props.options.fields?.[field]?.type || 'text'

const fieldOptionsFor = (field) =>
  (props.options.fields?.[field]?.options || []).map((value) => ({ label: humanize(value), value }))

const operatorRequiresValue = (operator) =>
  !['is_empty', 'is_not_empty', 'changed'].includes(operator)

const defaultConfigFor = (type) => {
  switch (type) {
    case 'assign_issue':
      return { user_id: null }
    case 'add_label':
    case 'remove_label':
      return { label_id: null }
    case 'change_priority':
      return { priority: 'high' }
    case 'add_comment':
      return { body: '' }
    case 'notify_users':
      return { recipients: [], message: '' }
    case 'set_due_date':
      return { offset_days: 3 }
    default:
      return {}
  }
}

const addCondition = () => {
  props.form.conditions.rules.push({
    field: fieldOptions.value[0]?.value || '',
    operator: 'equals',
    value: null,
  })
}

const removeCondition = (index) => props.form.conditions.rules.splice(index, 1)

const onConditionFieldChange = (condition) => {
  condition.value = null
}

const onTriggerChange = () => {
  const allowed = allowedFieldKeys.value
  props.form.conditions.rules = props.form.conditions.rules.filter((rule) =>
    allowed.includes(rule.field),
  )
}

const addAction = () => {
  props.form.actions.push({ type: 'change_priority', config: defaultConfigFor('change_priority') })
}

const removeAction = (index) => props.form.actions.splice(index, 1)

const onActionTypeChange = (action) => {
  action.config = defaultConfigFor(action.type)
}
</script>
