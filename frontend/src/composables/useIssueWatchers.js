import { ref } from 'vue'
import {
  fetchWatchers,
  fetchWatchStatus,
  watchIssue,
  unwatchIssue,
} from '@/services/issueWatchersApi'

/**
 * Manages the watchers of a single issue and the current user's watch state.
 */
export function useIssueWatchers(issueId) {
  const watchers = ref([])
  const isWatching = ref(false)
  const count = ref(0)
  const loading = ref(false)
  const toggling = ref(false)
  const error = ref(null)

  const load = async () => {
    loading.value = true
    try {
      const [listRes, statusRes] = await Promise.all([
        fetchWatchers(issueId),
        fetchWatchStatus(issueId),
      ])
      watchers.value = listRes.data.data || []
      isWatching.value = !!statusRes.data.watching
      count.value = statusRes.data.count ?? watchers.value.length
    } catch (e) {
      error.value = e
      console.error('Failed to load watchers:', e)
    } finally {
      loading.value = false
    }
  }

  const toggle = async () => {
    toggling.value = true
    error.value = null
    try {
      const { data } = isWatching.value
        ? await unwatchIssue(issueId)
        : await watchIssue(issueId)

      isWatching.value = !!data.watching
      count.value = data.count ?? count.value
      await load()
    } catch (e) {
      error.value = e
      throw e
    } finally {
      toggling.value = false
    }
  }

  return { watchers, isWatching, count, loading, toggling, error, load, toggle }
}
