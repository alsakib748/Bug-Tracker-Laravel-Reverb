import api from '@/services/api'

// ---- AI bug report assistant ----

export const analyzeBugReport = (projectId, payload) =>
  api.post(`/projects/${projectId}/ai/bug-report-analyses`, payload)

export const fetchBugReportAnalysis = (analysisId) =>
  api.get(`/ai/bug-report-analyses/${analysisId}`)

export const applyBugReportSuggestions = (analysisId, payload) =>
  api.post(`/ai/bug-report-analyses/${analysisId}/apply`, payload)

export const dismissBugReportAnalysis = (analysisId) =>
  api.post(`/ai/bug-report-analyses/${analysisId}/dismiss`)
