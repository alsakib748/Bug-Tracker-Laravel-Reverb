import api from '@/services/api'

// * Project integration (repository connection)
export const fetchGitHubStatus = (projectId) =>
  api.get(`/projects/${projectId}/integrations/github/status`)

export const connectGitHub = (projectId, payload) =>
  api.post(`/projects/${projectId}/integrations/github/connect`, payload)

export const fetchGitHubRepositories = (projectId) =>
  api.get(`/projects/${projectId}/integrations/github/repositories`)

export const linkRepository = (projectId, payload) =>
  api.post(`/projects/${projectId}/integrations/github/repositories`, payload)

export const syncRepositoryPullRequests = (projectId, repositoryId) =>
  api.post(`/projects/${projectId}/integrations/github/repositories/${repositoryId}/sync`)

export const unlinkRepository = (projectId, repositoryId) =>
  api.delete(`/projects/${projectId}/integrations/github/repositories/${repositoryId}`)

// * Issue-level PR/commit links
export const fetchIssueGitHubLinks = (issueId) =>
  api.get(`/issues/${issueId}/github-links`)

export const fetchGitHubLinkCandidates = (issueId) =>
  api.get(`/issues/${issueId}/github-links/candidates`)

export const createGitHubLink = (issueId, payload) =>
  api.post(`/issues/${issueId}/github-links`, payload)

export const removeGitHubLink = (issueId, linkId) =>
  api.delete(`/issues/${issueId}/github-links/${linkId}`)

// * Start the GitHub App installation flow.
// * GitHub redirects back to ${origin}/github?installation_id=...&setup_action=install
// * where the Integration page completes the connection.
export const startGitHubConnect = () => {
  const appSlug = import.meta.env.VITE_GITHUB_APP_SLUG
  const redirect = `${window.location.origin}/github`
  window.location.href = `https://github.com/apps/${appSlug}/installations/new?state=${encodeURIComponent(redirect)}`
}
