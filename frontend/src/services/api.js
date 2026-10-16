import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

// Track CSRF token state
let csrfTokenPromise = null

export const getCsrfToken = async () => {
  if (csrfTokenPromise) return csrfTokenPromise

  csrfTokenPromise = axios
    .get(`${import.meta.env.VITE_BACKEND_URL}/sanctum/csrf-cookie`, {
      withCredentials: true,
      headers: {
        Accept: 'application/json',
      },
    })
    .then(() => {
      return true
    })
    .catch((error) => {
      csrfTokenPromise = null // Reset on failure
      throw error
    })

  return csrfTokenPromise
}

export const resetCsrfToken = () => {
  csrfTokenPromise = null
}

// If the session/CSRF expires (419), drop the cached token promise so the
// next request fetches a fresh csrf-cookie.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 419) {
      csrfTokenPromise = null
    }
    return Promise.reject(error)
  }
)

export default api
