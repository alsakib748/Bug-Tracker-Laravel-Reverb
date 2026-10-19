export const ACTION_COLORS = {
  brand: {
    soft: 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    ring: 'ring-brand-200 dark:ring-brand-500/30',
    dot: 'bg-brand-500',
  },
  info: {
    soft: 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400',
    ring: 'ring-blue-light-200 dark:ring-blue-light-500/30',
    dot: 'bg-blue-light-500',
  },
  success: {
    soft: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
    ring: 'ring-success-200 dark:ring-success-500/30',
    dot: 'bg-success-500',
  },
  warning: {
    soft: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
    ring: 'ring-warning-200 dark:ring-warning-500/30',
    dot: 'bg-warning-500',
  },
  error: {
    soft: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    ring: 'ring-error-200 dark:ring-error-500/30',
    dot: 'bg-error-500',
  },
  purple: {
    soft: 'bg-theme-purple-500/10 text-theme-purple-500 dark:bg-theme-purple-500/15 dark:text-theme-purple-500',
    ring: 'ring-theme-purple-500/25',
    dot: 'bg-theme-purple-500',
  },
  gray: {
    soft: 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400',
    ring: 'ring-gray-200 dark:ring-white/10',
    dot: 'bg-gray-400',
  },
}

export const ACTIVITY_META = {
  project_created: {
    label: 'Project Created',
    icon: 'pi pi-folder-plus',
    color: 'brand',
    category: 'Project',
  },
  project_updated: {
    label: 'Project Updated',
    icon: 'pi pi-file-edit',
    color: 'info',
    category: 'Project',
  },
  project_deleted: {
    label: 'Project Deleted',
    icon: 'pi pi-trash',
    color: 'error',
    category: 'Project',
  },
  member_added: {
    label: 'Member Added',
    icon: 'pi pi-user-plus',
    color: 'success',
    category: 'Members',
  },
  member_removed: {
    label: 'Member Removed',
    icon: 'pi pi-user-minus',
    color: 'error',
    category: 'Members',
  },
  issue_created: {
    label: 'Issue Created',
    icon: 'pi pi-plus-circle',
    color: 'success',
    category: 'Issues',
  },
  issue_updated: {
    label: 'Issue Updated',
    icon: 'pi pi-sync',
    color: 'info',
    category: 'Issues',
  },
  issue_assigned: {
    label: 'Issue Assigned',
    icon: 'pi pi-user-edit',
    color: 'info',
    category: 'Issues',
  },
  issue_reassigned: {
    label: 'Issue Reassigned',
    icon: 'pi pi-users',
    color: 'purple',
    category: 'Issues',
  },
  issue_status_changed: {
    label: 'Status Changed',
    icon: 'pi pi-refresh',
    color: 'warning',
    category: 'Issues',
  },
  issue_closed: {
    label: 'Issue Closed',
    icon: 'pi pi-check-circle',
    color: 'success',
    category: 'Issues',
  },
  issue_reopened: {
    label: 'Issue Reopened',
    icon: 'pi pi-history',
    color: 'warning',
    category: 'Issues',
  },
  comment_added: {
    label: 'Comment Added',
    icon: 'pi pi-comment',
    color: 'brand',
    category: 'Comments',
  },
  comment_updated: {
    label: 'Comment Updated',
    icon: 'pi pi-pencil',
    color: 'info',
    category: 'Comments',
  },
  comment_deleted: {
    label: 'Comment Deleted',
    icon: 'pi pi-comments',
    color: 'error',
    category: 'Comments',
  },
  attachment_uploaded: {
    label: 'Attachment Uploaded',
    icon: 'pi pi-paperclip',
    color: 'purple',
    category: 'Attachments',
  },
  attachment_deleted: {
    label: 'Attachment Deleted',
    icon: 'pi pi-trash',
    color: 'error',
    category: 'Attachments',
  },
  user_login: {
    label: 'User Login',
    icon: 'pi pi-sign-in',
    color: 'success',
    category: 'Authentication',
  },
  user_logout: {
    label: 'User Logout',
    icon: 'pi pi-sign-out',
    color: 'gray',
    category: 'Authentication',
  },
}

const DEFAULT_META = {
  label: 'Activity',
  icon: 'pi pi-bolt',
  color: 'gray',
  category: 'Other',
}

export const getActivityMeta = (action) =>
  ACTIVITY_META[action] || { ...DEFAULT_META, label: action || DEFAULT_META.label }

export const getActionStyles = (action) => {
  const meta = getActivityMeta(action)
  return { ...meta, ...ACTION_COLORS[meta.color] }
}

const GROUP_ORDER = ['Project', 'Members', 'Issues', 'Comments', 'Attachments', 'Authentication']

export const ACTION_FILTER_GROUPS = GROUP_ORDER.map((category) => ({
  label: category,
  options: Object.entries(ACTIVITY_META)
    .filter(([, meta]) => meta.category === category)
    .map(([value, meta]) => ({ label: meta.label, value })),
})).filter((group) => group.options.length > 0)
