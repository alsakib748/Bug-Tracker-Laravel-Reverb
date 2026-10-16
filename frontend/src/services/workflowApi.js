import api from '@/services/api'

// Human-readable labels for the issue workflow statuses.
export const STATUS_LABELS = {
  open: 'Open',
  assigned: 'Assigned',
  in_progress: 'In Progress',
  code_review: 'Code Review',
  testing: 'Testing',
  resolved: 'Resolved',
  closed: 'Closed',
  reopened: 'Reopened',
}

export const statusLabel = (status) =>
  status ? STATUS_LABELS[status] || status : 'Created'

export const fetchStatusHistory = (issueId, params = {}) =>
  api.get(`/issues/${issueId}/status-history`, { params })

export const fetchAllowedTransitions = (issueId) =>
  api.get(`/issues/${issueId}/allowed-transitions`)

export const transitionIssue = (issueId, toStatus, reason = null) =>
  api.post(`/issues/${issueId}/transitions`, {
    to_status: toStatus,
    reason,
  })
