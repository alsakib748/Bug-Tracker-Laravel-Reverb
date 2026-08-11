import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Track CSRF token state
let csrfTokenPromise = null

export const getCsrfToken = async () => {
  if (csrfTokenPromise) return csrfTokenPromise

  // csrfTokenPromise = api
  //   .get('/sanctum/csrf-cookie')
  //   .then(() => {
  //     return true
  //   })
  //   .catch((error) => {
  //     csrfTokenPromise = null // Reset on failure
  //     throw error
  //   })

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

export default api
