import api from '@/services/api'

export const fetchInsights = (projectId) =>
  api.get(`/projects/${projectId}/insights`)

export const requestInsight = (projectId, payload) =>
  api.post(`/projects/${projectId}/insights`, payload)

export const fetchInsight = (id) => api.get(`/insights/${id}`)
