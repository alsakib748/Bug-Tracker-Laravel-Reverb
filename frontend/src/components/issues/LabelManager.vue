<template>
  <Dialog :visible="modelValue" @update:visible="$emit('update:modelValue', $event)" modal header="Project labels"
    :style="{ width: '32rem' }">
    <div class="space-y-4">
      <div v-if="labels.length === 0" class="text-sm text-gray-500">
        No labels yet. Add the first one below.
      </div>

      <ul v-else class="space-y-2">
        <li v-for="label in labels" :key="label.id" class="flex items-center gap-2">
          <span class="w-4 h-4 rounded-full shrink-0"
            :style="{ backgroundColor: label.color || '#64748b' }"></span>
          <span class="flex-1 text-sm text-gray-800">
            {{ label.name }}
            <span v-if="label.description" class="text-gray-400">— {{ label.description }}</span>
          </span>
          <Button icon="pi pi-pencil" class="p-button-text p-button-sm" @click="startEdit(label)" />
          <Button icon="pi pi-trash" class="p-button-text p-button-sm p-button-danger" :disabled="saving"
            @click="remove(label)" />
        </li>
      </ul>

      <div class="border-t pt-4 space-y-2">
        <div class="flex gap-2">
          <InputText v-model="form.name" placeholder="Label name" class="flex-1" maxlength="80" />
          <input v-model="form.color" type="color" class="h-9 w-12 rounded border border-gray-300" />
        </div>
        <InputText v-model="form.description" placeholder="Description (optional)" class="w-full" maxlength="255" />
        <div class="flex gap-2">
          <Button :label="editingId ? 'Update label' : 'Add label'" :loading="saving"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" @click="submit" />
          <Button v-if="editingId" label="Cancel" class="p-button-text" @click="reset" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { createLabel, updateLabel, deleteLabel } from '@/services/labelsApi'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  projectId: {
    type: [String, Number],
    default: null,
  },
  labels: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'changed'])

const form = ref({ name: '', color: '#64748b', description: '' })
const editingId = ref(null)
const saving = ref(false)
const error = ref(null)

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) reset()
  }
)

const reset = () => {
  form.value = { name: '', color: '#64748b', description: '' }
  editingId.value = null
  error.value = null
}

const startEdit = (label) => {
  editingId.value = label.id
  form.value = {
    name: label.name,
    color: label.color || '#64748b',
    description: label.description || '',
  }
}

const submit = async () => {
  if (!form.value.name.trim()) {
    error.value = 'Label name is required.'
    return
  }

  saving.value = true
  error.value = null
  try {
    if (editingId.value) {
      await updateLabel(editingId.value, form.value)
    } else {
      await createLabel(props.projectId, form.value)
    }
    reset()
    emit('changed')
  } catch (e) {
    error.value =
      e.response?.data?.message ||
      Object.values(e.response?.data?.errors || {}).flat()[0] ||
      'Failed to save label.'
  } finally {
    saving.value = false
  }
}

const remove = async (label) => {
  saving.value = true
  error.value = null
  try {
    await deleteLabel(label.id)
    emit('changed')
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to delete label.'
  } finally {
    saving.value = false
  }
}
</script>
