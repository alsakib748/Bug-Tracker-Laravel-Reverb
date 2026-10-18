import { ref } from 'vue'
import { fetchRoles } from '@/services/rolesApi'

const FALLBACK_ROLES = [
  'Admin',
  'Project Manager',
  'Developer',
  'QA Tester',
  'Reporter',
  'Viewer',
]

export function useRoleOptions() {
  const roleOptions = ref(
    FALLBACK_ROLES.map((name) => ({
      label: name,
      value: name,
      permissionsCount: null,
      isProtected: name === 'Admin',
    })),
  )
  const loading = ref(false)

  const load = async () => {
    loading.value = true
    try {
      const { data } = await fetchRoles()
      const roles = data.data || []
      if (roles.length) {
        roleOptions.value = roles.map((role) => ({
          label: role.name,
          value: role.name,
          permissionsCount: role.permissions?.length ?? 0,
          isProtected: role.is_protected,
        }))
      }
    } catch (error) {
      console.error('Failed to load roles:', error)
    } finally {
      loading.value = false
    }
  }

  const leastPrivileged = () => {
    const assignable = roleOptions.value.filter((option) => !option.isProtected)
    if (!assignable.length) return null

    const withCounts = assignable.filter((option) => option.permissionsCount !== null)
    if (withCounts.length) {
      return withCounts.sort((a, b) => a.permissionsCount - b.permissionsCount)[0].value
    }

    return assignable.find((option) => option.value === 'Reporter')?.value ?? assignable.at(-1).value
  }

  return { roleOptions, loading, load, leastPrivileged }
}
