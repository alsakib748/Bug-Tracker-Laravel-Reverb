import { ref } from 'vue'
import { fetchLinks, createLink, deleteLink } from '@/services/issueLinksApi'

/**
 * Manages the links between the current issue and other issues.
 */
export function useIssueLinks(issueId) {
  const links = ref([])
  const loading = ref(false)
  const creating = ref(false)
  const error = ref(null)

  const load = async () => {
    loading.value = true
    try {
      const { data } = await fetchLinks(issueId)
      links.value = data.data || []
    } catch (e) {
      error.value = e
      console.error('Failed to load issue links:', e)
    } finally {
      loading.value = false
    }
  }

  const create = async (payload) => {
    creating.value = true
    error.value = null
    try {
      await createLink(issueId, payload)
      await load()
    } catch (e) {
      error.value = e
      throw e
    } finally {
      creating.value = false
    }
  }

  const remove = async (link) => {
    error.value = null
    try {
      await deleteLink(link.id)
      links.value = links.value.filter((item) => item.id !== link.id)
    } catch (e) {
      error.value = e
      throw e
    }
  }

  return { links, loading, creating, error, load, create, remove }
}
