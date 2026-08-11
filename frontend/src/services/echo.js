import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from './api'

window.Pusher = Pusher

const getCookie = (name) => {
  const match = document.cookie.match(
    new RegExp('(^|;\\s*)' + name + '=([^;]*)')
  )
  return match ? decodeURIComponent(match[2]) : null
}

// Custom authorizer that uses axios so cookies (session + XSRF) are sent.
// Pusher's built-in XHR auth does NOT send cross-origin cookies, which
// breaks Sanctum's session-based auth on /broadcasting/auth.
const authorizer = (channel, options) => {
  return {
    authorize: (socketId, callback) => {
      api
        .post(
          '/broadcasting/auth',
          {
            socket_id: socketId,
            channel_name: channel.name,
          },
          {
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
          }
        )
        .then((response) => {
          callback(null, response.data)
        })
        .catch((error) => {
          callback(error, null)
        })
    },
  }
}

const echo = new Echo({
  broadcaster: 'reverb',

  key: import.meta.env.VITE_REVERB_APP_KEY,

  wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',

  wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,

  wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,

  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',

  enabledTransports: ['ws', 'wss'],

  // Custom authorizer sends cookies (session + XSRF) and proper headers.
  authorizer: authorizer,

  // Keep the legacy headers/endpoint around as a fallback so other Pusher
  // internal calls (e.g. /pusher/auth on Reverb) still work.
  authEndpoint: `${import.meta.env.VITE_BACKEND_URL}/api/broadcasting/auth`,

  auth: {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      get 'X-XSRF-TOKEN'() {
        return getCookie('XSRF-TOKEN')
      },
    },
  },

  withCredentials: true,
})

export default echo
