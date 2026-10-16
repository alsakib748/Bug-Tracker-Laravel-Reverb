import { ref } from 'vue'
import {
  fetchStatusHistory,
  fetchAllowedTransitions,
  transitionIssue,
} from '@/services/workflowApi'

/**
 * Composable that manages the issue workflow: status history, the allowed
 * next transitions for the current status, and performing a transition.
 */
export function useIssueWorkflow(issueId) {
  const history = ref([])
  const allowedTransitions = ref([])
  const loading = ref(false)
  const transitioning = ref(false)
  const error = ref(null)

  const loadHistory = async (params = {}) => {
    loading.value = true
    try {
      const { data } = await fetchStatusHistory(issueId, params)
      history.value = data.data || []
    } catch (e) {
      error.value = e
      console.error('Failed to load status history:', e)
    } finally {
      loading.value = false
    }
  }

  const loadAllowedTransitions = async () => {
    try {
      const { data } = await fetchAllowedTransitions(issueId)
      allowedTransitions.value = data.allowed || []
    } catch (e) {
      console.error('Failed to load allowed transitions:', e)
    }
  }

  const performTransition = async (toStatus, reason = null) => {
    transitioning.value = true
    error.value = null
    try {
      const { data } = await transitionIssue(issueId, toStatus, reason)
      await loadHistory()
      await loadAllowedTransitions()
      return data.data
    } catch (e) {
      error.value = e.response?.data?.message || 'Transition failed'
      throw e
    } finally {
      transitioning.value = false
    }
  }

  return {
    history,
    allowedTransitions,
    loading,
    transitioning,
    error,
    loadHistory,
    loadAllowedTransitions,
    performTransition,
  }
}
