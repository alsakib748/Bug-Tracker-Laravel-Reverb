import api from '@/services/api'

export const fetchBacklog = (projectId) =>
  api.get(`/projects/${projectId}/backlog`)

export const reorderBacklogIssue = (issueId, position) =>
  api.patch(`/issues/${issueId}/backlog-position`, { position })
