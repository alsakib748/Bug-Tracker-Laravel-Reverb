import api from '@/services/api'

export const fetchOverview = (projectId, params = {}) =>
  api.get(`/projects/${projectId}/analytics/overview`, { params })

export const fetchTrends = (projectId, params = {}) =>
  api.get(`/projects/${projectId}/analytics/trends`, { params })

export const fetchWorkflow = (projectId, params = {}) =>
  api.get(`/projects/${projectId}/analytics/workflow`, { params })
