import { ref, computed } from 'vue'
import {
  fetchIssueLabels,
  fetchProjectLabels,
  syncIssueLabels,
} from '@/services/labelsApi'

/**
 * Manages the labels attached to a single issue together with the pool of
 * labels available in the issue's project.
 */
export function useIssueLabels(issueId) {
  const issueLabels = ref([])
  const projectLabels = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)

  const availableLabels = computed(() => {
    const attached = new Set(issueLabels.value.map((label) => label.id))
    return projectLabels.value.filter((label) => !attached.has(label.id))
  })

  const loadIssueLabels = async () => {
    loading.value = true
    try {
      const { data } = await fetchIssueLabels(issueId)
      issueLabels.value = data.data || []
    } catch (e) {
      error.value = e
      console.error('Failed to load issue labels:', e)
    } finally {
      loading.value = false
    }
  }

  const loadProjectLabels = async (projectId) => {
    if (!projectId) return
    try {
      const { data } = await fetchProjectLabels(projectId)
      projectLabels.value = data.data || []
    } catch (e) {
      console.error('Failed to load project labels:', e)
    }
  }

  const updateLabels = async (labelIds) => {
    saving.value = true
    error.value = null
    try {
      const { data } = await syncIssueLabels(issueId, labelIds)
      issueLabels.value = data.data || []
    } catch (e) {
      error.value = e
      throw e
    } finally {
      saving.value = false
    }
  }

  return {
    issueLabels,
    projectLabels,
    availableLabels,
    loading,
    saving,
    error,
    loadIssueLabels,
    loadProjectLabels,
    updateLabels,
  }
}
