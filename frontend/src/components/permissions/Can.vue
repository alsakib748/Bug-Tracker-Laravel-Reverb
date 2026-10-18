<template>
  <slot v-if="allowed" />
  <slot v-else name="fallback" />
</template>

<script setup>
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  permission: { type: [String, Array], default: null },
  role: { type: [String, Array], default: null },
  any: { type: Boolean, default: false },
  all: { type: Boolean, default: false },
})

const { hasPermission, hasAnyPermission, hasAllPermissions, hasRole } =
  usePermissions()

const allowed = computed(() => {
  const checks = []

  if (props.permission) {
    const list = Array.isArray(props.permission)
      ? props.permission
      : [props.permission]

    if (props.all) {
      checks.push(hasAllPermissions(list))
    } else if (props.any || list.length > 1) {
      checks.push(hasAnyPermission(list))
    } else {
      checks.push(hasPermission(list[0]))
    }
  }

  if (props.role) {
    const list = Array.isArray(props.role) ? props.role : [props.role]
    checks.push(list.some((role) => hasRole(role)))
  }

  if (checks.length === 0) return true

  return props.all ? checks.every(Boolean) : checks.some(Boolean)
})
</script>
