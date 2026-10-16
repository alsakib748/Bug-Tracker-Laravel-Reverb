export const extractErrorMessage = (error) => {
  if (error.response) {
    const data = error.response.data

    // 1. If there's a 'message' field, use it
    if (data?.message) {
      return data.message
    }

    // 2. If there are validation errors, build a combined message
    if (data?.errors && typeof data.errors === 'object') {
      const messages = Object.values(data.errors).flat()
      return messages.join(' ') // or join with '\n' if your toast supports multi-line
    }

    // 3. Fallback to status text
    return `Error ${error.response.status}: ${error.response.statusText}`
  }

  if (error.request) {
    return 'No response from server. Please check your connection.'
  }

  return error.message || 'An unexpected error occurred.'
}
