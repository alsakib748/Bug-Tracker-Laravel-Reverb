import { ref } from 'vue'
import {
  fetchIssueGitHubLinks,
  fetchGitHubLinkCandidates,
  createGitHubLink,
  removeGitHubLink,
} from '@/services/githubApi'

/**
 * Manages GitHub PR/commit links for the current issue.
 */
export function useGitHubLinks(issueId) {
  const links = ref([])
  const candidates = ref([])
  const loading = ref(false)
  const linking = ref(false)
  const error = ref(null)

  const load = async () => {
    loading.value = true
    try {
      const { data } = await fetchIssueGitHubLinks(issueId)
      links.value = data.data || []
    } catch (e) {
      error.value = e
      console.error('Failed to load GitHub links:', e)
    } finally {
      loading.value = false
    }
  }

  const loadCandidates = async () => {
    try {
      const { data } = await fetchGitHubLinkCandidates(issueId)
      candidates.value = data.data || []
    } catch (e) {
      console.error('Failed to load GitHub link candidates:', e)
    }
  }

  const link = async (pullRequestId) => {
    linking.value = true
    error.value = null
    try {
      await createGitHubLink(issueId, { github_pull_request_id: pullRequestId })
      await Promise.all([load(), loadCandidates()])
    } catch (e) {
      error.value = e
      throw e
    } finally {
      linking.value = false
    }
  }

  const remove = async (link) => {
    error.value = null
    try {
      await removeGitHubLink(issueId, link.id)
      links.value = links.value.filter((item) => item.id !== link.id)
      await loadCandidates()
    } catch (e) {
      error.value = e
      throw e
    }
  }

  return { links, candidates, loading, linking, error, load, loadCandidates, link, remove }
}
