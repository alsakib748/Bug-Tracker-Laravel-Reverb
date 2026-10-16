import api from '@/services/api'

export const LINK_TYPE_OPTIONS = [
  { label: 'Blocks', value: 'blocks' },
  { label: 'Duplicates', value: 'duplicates' },
  { label: 'Relates to', value: 'relates_to' },
]

export const fetchLinks = (issueId) =>
  api.get(`/issues/${issueId}/links`)

export const createLink = (issueId, payload) =>
  api.post(`/issues/${issueId}/links`, payload)

export const deleteLink = (linkId) =>
  api.delete(`/issue-links/${linkId}`)
