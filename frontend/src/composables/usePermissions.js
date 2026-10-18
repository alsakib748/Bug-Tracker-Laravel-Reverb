import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const auth = useAuthStore()

  const isAdmin = computed(() => auth.isAdmin)
  const roles = computed(() => auth.roles)
  const permissions = computed(() => auth.permissions)

  const hasRole = (role) => auth.hasRole(role)

  const hasPermission = (permission) => auth.hasPermission(permission)

  const hasAnyPermission = (list = []) => auth.hasAnyPermission(list)

  const hasAllPermissions = (list = []) => auth.hasAllPermissions(list)

  const can = (permission) => {
    if (Array.isArray(permission)) return hasAnyPermission(permission)
    return hasPermission(permission)
  }

  return {
    isAdmin,
    roles,
    permissions,
    hasRole,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    can,
  }
}
