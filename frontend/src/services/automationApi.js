import api from '@/services/api'

// ---- Automation rules ----

export const fetchAutomationOptions = () => api.get('/automation-options')

export const fetchProjectRules = (projectId) =>
  api.get(`/projects/${projectId}/automation-rules`)

export const createAutomationRule = (projectId, payload) =>
  api.post(`/projects/${projectId}/automation-rules`, payload)

export const fetchAutomationRule = (ruleId) =>
  api.get(`/automation-rules/${ruleId}`)

export const updateAutomationRule = (ruleId, payload) =>
  api.put(`/automation-rules/${ruleId}`, payload)

export const enableAutomationRule = (ruleId) =>
  api.post(`/automation-rules/${ruleId}/enable`)

export const disableAutomationRule = (ruleId) =>
  api.post(`/automation-rules/${ruleId}/disable`)

export const deleteAutomationRule = (ruleId) =>
  api.delete(`/automation-rules/${ruleId}`)

export const fetchProjectAutomationExecutions = (projectId, params = {}) =>
  api.get(`/projects/${projectId}/automation-executions`, { params })
