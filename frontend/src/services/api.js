import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  // Laravel only validates CSRF on state-changing methods. Attaching the
  // X-XSRF-TOKEN header to safe methods (GET/HEAD/OPTIONS) turns every read
  // into a non-simple request, which forces a CORS preflight per call. We add
  // the header ourselves below, only where it is actually required.
  withXSRFToken: false,
  // Cap request time so a hung backend (e.g. the GitHub OAuth connect
  // round-trip) surfaces as a catchable timeout instead of spinning forever.
  timeout: 30000,
  headers: {
    Accept: 'application/json',
  },
})

const SAFE_METHODS = ['get', 'head', 'options']

const readCookie = (name) => {
  if (typeof document === 'undefined') return null
  const escaped = name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1')
  const match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

api.interceptors.request.use((config) => {
  const method = (config.method || 'get').toLowerCase()
  if (!SAFE_METHODS.includes(method)) {
    const token = readCookie('XSRF-TOKEN')
    if (token && config.headers) {
      if (typeof config.headers.set === 'function') {
        config.headers.set('X-XSRF-TOKEN', token)
      } else {
        config.headers['X-XSRF-TOKEN'] = token
      }
    }
  }
  return config
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
  },
)

export default api
