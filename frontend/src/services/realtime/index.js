// Reconnect recovery and project subscriptions ---------------------------------
//
// This module intentionally does NOT import `services/echo` at the top level:
// doing so would pull pusher-js + laravel-echo (~80 kB) into every page,
// including the login screen that never opens a websocket. The Echo client is
// dynamically imported the first time a channel is actually opened, and the
// reconnect registry lives in dependency-free `./connection`.
import { onRealtimeReconnect } from './connection'

export { onRealtimeReconnect }

/**
 * Subscribe to a user's private channel and invoke a handler for each event.
 * Returns a function that tears down the subscription.
 *
 * Issue events are broadcast per-user (see the backend's
 * `BroadcastsToProjectAudience` trait), so a single `user.{id}` channel keeps a
 * client live across every project it can see — instead of one `project.{id}`
 * channel per project.
 *
 * The Echo client is loaded lazily, so the first call resolves asynchronously;
 * the returned teardown is safe to call before the client finishes loading.
 */
export function subscribeToUser(userId, events, handler) {
  if (!userId) return () => {}

  let disposed = false
  let unsub = null

  import('../echo')
    .then(({ default: echo }) => {
      if (disposed || !echo) return

      const channel = echo.private(`user.${userId}`)

      events.forEach((event) => channel.listen(event, handler))

      unsub = () => {
        events.forEach((event) => channel.stopListening(event))
        echo.leave(`user.${userId}`)
      }
    })
    .catch((error) => {
      console.error('Failed to load realtime client:', error)
    })

  return () => {
    disposed = true
    if (unsub) unsub()
  }
}

/**
 * Trailing-edge debounce, used to coalesce bursts of realtime events into a
 * single refetch.
 */
export function debounce(fn, wait = 400) {
  let timer = null

  return (...args) => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      timer = null
      fn(...args)
    }, wait)
  }
}
