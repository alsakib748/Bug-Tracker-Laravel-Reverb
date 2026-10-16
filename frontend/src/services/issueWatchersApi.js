import api from '@/services/api'

export const fetchWatchers = (issueId) =>
  api.get(`/issues/${issueId}/watchers`)

export const fetchWatchStatus = (issueId) =>
  api.get(`/issues/${issueId}/watchers/me`)

export const watchIssue = (issueId) =>
  api.post(`/issues/${issueId}/watchers`)

export const unwatchIssue = (issueId) =>
  api.delete(`/issues/${issueId}/watchers/me`)
