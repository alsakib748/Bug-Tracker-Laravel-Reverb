import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
})

// Track CSRF token state
let csrfTokenPromise = null

export const getCsrfToken = async () => {
  if (csrfTokenPromise) return csrfTokenPromise

  csrfTokenPromise = api
    .get('/sanctum/csrf-cookie')
    .then(() => {
      return true
    })
    .catch(() => {
      csrfTokenPromise = null // Reset on failure
      throw error
    })

  return csrfTokenPromise
}

export const resetCsrfToken = () => {
  csrfTokenPromise = null
}

export default api
