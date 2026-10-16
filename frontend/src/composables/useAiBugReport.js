import { ref } from 'vue'
import {
  analyzeBugReport,
  fetchBugReportAnalysis,
  applyBugReportSuggestions,
  dismissBugReportAnalysis,
} from '@/services/aiBugReportApi'

const TERMINAL_STATUSES = ['ready', 'failed', 'dismissed']
const POLL_INTERVAL = 1500
const POLL_TIMEOUT = 90000

const FAILURE_MESSAGES = {
  provider_rate_limited: 'The AI provider is rate limiting requests. Please try again later.',
  insufficient_credits: 'The AI provider account has no credits left.',
  provider_overloaded: 'The AI provider is overloaded right now. Please try again shortly.',
  provider_unreachable: 'The AI provider could not be reached. Please try again shortly.',
  provider_error: 'The AI provider could not complete the analysis.',
}

const failureMessage = (code) =>
  FAILURE_MESSAGES[code] ||
  'The AI analysis could not be completed. Please try again.'

/**
 * Drives one AI bug report analysis: queue it, poll until it is ready, then
 * apply or dismiss the suggestions.
 */
export function useAiBugReport() {
  const analyzing = ref(false)
  const applying = ref(false)
  const status = ref(null)
  const analysisId = ref(null)
  const analysis = ref(null)
  const suggestions = ref(null)
  const error = ref(null)

  let pollTimer = null
  let pollStartedAt = null

  const stopPolling = () => {
    if (pollTimer) {
      clearTimeout(pollTimer)
      pollTimer = null
    }
  }

  const reset = () => {
    stopPolling()
    analyzing.value = false
    applying.value = false
    status.value = null
    analysisId.value = null
    analysis.value = null
    suggestions.value = null
    error.value = null
  }

  const poll = () => {
    pollTimer = setTimeout(async () => {
      if (pollStartedAt && Date.now() - pollStartedAt > POLL_TIMEOUT) {
        error.value =
          'The analysis is taking longer than expected. Please retry, and make sure a queue worker is running.'
        analyzing.value = false
        return
      }

      try {
        const { data } = await fetchBugReportAnalysis(analysisId.value)
        const payload = data.data

        analysis.value = payload
        status.value = payload.status

        if (TERMINAL_STATUSES.includes(payload.status)) {
          suggestions.value = payload.suggestions || null

          if (payload.status === 'ready') {
            analyzing.value = false
          } else if (payload.status === 'failed') {
            analyzing.value = false
            error.value = failureMessage(payload.error_code)
          }
          return
        }

        poll()
      } catch (e) {
        analyzing.value = false
        error.value = e.response?.data?.message || 'Failed to fetch the analysis.'
      }
    }, POLL_INTERVAL)
  }

  const analyze = async (projectId, payload) => {
    reset()
    analyzing.value = true

    try {
      const { data } = await analyzeBugReport(projectId, payload)
      analysisId.value = data.data.id
      status.value = data.data.status
      pollStartedAt = Date.now()
      poll()
      return analysisId.value
    } catch (e) {
      analyzing.value = false
      error.value = e.response?.data?.message || 'Failed to start the analysis.'
      throw e
    }
  }

  const apply = async (payload) => {
    applying.value = true
    error.value = null

    try {
      const { data } = await applyBugReportSuggestions(analysisId.value, payload)
      status.value = data.issue ? 'applied' : 'applied'
      return data
    } catch (e) {
      error.value = e.response?.data?.message || 'Failed to apply the suggestions.'
      throw e
    } finally {
      applying.value = false
    }
  }

  const dismiss = async () => {
    stopPolling()

    try {
      await dismissBugReportAnalysis(analysisId.value)
      status.value = 'dismissed'
    } catch (e) {
      error.value = e.response?.data?.message || 'Failed to dismiss the analysis.'
    }
  }

  return {
    analyzing,
    applying,
    status,
    analysisId,
    analysis,
    suggestions,
    error,
    analyze,
    apply,
    dismiss,
    reset,
    stopPolling,
  }
}
