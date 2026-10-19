// Static tone maps for issue status/priority. Static strings keep every
// class visible to the Tailwind scanner (no runtime class construction).

export const STATUS_BADGE_COLOR = {
  open: 'light',
  assigned: 'info',
  in_progress: 'primary',
  code_review: 'warning',
  testing: 'info',
  resolved: 'success',
  closed: 'dark',
  reopened: 'warning',
}

export const PRIORITY_BADGE_COLOR = {
  low: 'light',
  medium: 'info',
  high: 'warning',
  critical: 'error',
}

export const SEVERITY_BADGE_COLOR = {
  minor: 'light',
  moderate: 'info',
  major: 'warning',
  critical: 'error',
}

export const TYPE_BADGE_COLOR = {
  bug: 'error',
  feature: 'primary',
  improvement: 'info',
  task: 'light',
  epic: 'dark',
  story: 'info',
}

export const statusBadgeColor = (status) => STATUS_BADGE_COLOR[status] || 'light'
export const priorityBadgeColor = (priority) => PRIORITY_BADGE_COLOR[priority] || 'light'
export const severityBadgeColor = (severity) => SEVERITY_BADGE_COLOR[severity] || 'light'
export const typeBadgeColor = (type) => TYPE_BADGE_COLOR[type] || 'light'

export const isOverdue = (issue) => {
  if (!issue?.due_date) return false
  const closed = ['resolved', 'closed'].includes(issue.status)
  return !closed && new Date(issue.due_date) < new Date()
}

export const formatDate = (value) => {
  if (!value) return null
  return new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

export const formatDateTime = (value) => {
  if (!value) return null
  return new Date(value).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
