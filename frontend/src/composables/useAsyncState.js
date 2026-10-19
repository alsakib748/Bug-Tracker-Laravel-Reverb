import { ref } from 'vue'

export function useAsyncState(asyncFn, initialData = null, { immediate = true } = {}) {
  const data = ref(initialData)
  const loading = ref(false)
  const error = ref(null)

  const execute = async (...args) => {
    loading.value = true
    error.value = null
    try {
      const result = await asyncFn(...args)
      data.value = result
      return result
    } catch (err) {
      error.value = err
      throw err
    } finally {
      loading.value = false
    }
  }

  if (immediate && typeof asyncFn === 'function') {
    // Don't auto-execute if called with a function that expects args? For
    // convenience, only auto-run when no args expected contextually. But
    // better to let caller control; keep immediate true only if safe.
    // Heuristic: if it has 0 args conceptually, we skip auto to avoid surprises.
    // However, follow existing usage patterns; leave as-is but safe.
    // To be explicit, don't auto-run if immediate is true but caller must pass.
    // Keep simple: run if immediate.
    execute()
  }

  return {
    data,
    loading,
    error,
    execute,
    setData: (value) => (data.value = value),
    reset: () => {
      data.value = initialData
      loading.value = false
      error.value = null
    },
  }
}
