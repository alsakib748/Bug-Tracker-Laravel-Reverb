import api from '@/services/api'

export const fetchSprints = (projectId) =>
  api.get(`/projects/${projectId}/sprints`)

export const createSprint = (projectId, data) =>
  api.post(`/projects/${projectId}/sprints`, data)

export const fetchSprint = (sprintId) =>
  api.get(`/sprints/${sprintId}`)

export const updateSprint = (sprintId, data) =>
  api.put(`/sprints/${sprintId}`, data)

export const addIssuesToSprint = (sprintId, issueIds) =>
  api.post(`/sprints/${sprintId}/issues`, { issue_ids: issueIds })

export const removeIssueFromSprint = (sprintId, issueId) =>
  api.delete(`/sprints/${sprintId}/issues/${issueId}`)

export const startSprint = (sprintId) =>
  api.post(`/sprints/${sprintId}/start`)

export const completeSprint = (sprintId, data) =>
  api.post(`/sprints/${sprintId}/complete`, data)

export const cancelSprint = (sprintId) =>
  api.post(`/sprints/${sprintId}/cancel`)

export const fetchSprintReport = (sprintId) =>
  api.get(`/sprints/${sprintId}/report`)
