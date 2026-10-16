import api from '@/services/api'

// ---- Project labels ----
export const fetchProjectLabels = (projectId) =>
  api.get(`/projects/${projectId}/labels`)

export const createLabel = (projectId, payload) =>
  api.post(`/projects/${projectId}/labels`, payload)

export const updateLabel = (labelId, payload) =>
  api.put(`/labels/${labelId}`, payload)

export const deleteLabel = (labelId) =>
  api.delete(`/labels/${labelId}`)

// ---- Issue labels ----
export const fetchIssueLabels = (issueId) =>
  api.get(`/issues/${issueId}/labels`)

export const syncIssueLabels = (issueId, labelIds) =>
  api.put(`/issues/${issueId}/labels`, { label_ids: labelIds })

export const attachIssueLabel = (issueId, labelId) =>
  api.post(`/issues/${issueId}/labels`, { label_ids: [labelId] })

export const detachIssueLabel = (issueId, labelId) =>
  api.delete(`/issues/${issueId}/labels/${labelId}`)
