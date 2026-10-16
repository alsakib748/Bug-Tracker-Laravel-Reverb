import api from '@/services/api'

export const fetchBoard = (projectId, sprintId = null) =>
  api.get(`/projects/${projectId}/board`, {
    params: sprintId ? { sprint_id: sprintId } : {},
  })

export const moveBoardIssue = (issueId, status, position) =>
  api.patch(`/issues/${issueId}/board-position`, { status, position })
