import echo from '../echo'

// Reconnect recovery -------------------------------------------------------
//
// Pusher/Reverb automatically re-subscribes channels after a dropped
// connection, but messages published while offline are not replayed. Views
// register a callback here so they can refetch their data once the socket is
// back. The first "connected" event is ignored because the view already loads
// its data on mount.
const reconnectCallbacks = new Set()
let hasConnected = false

const connection = echo?.connector?.pusher?.connection

if (connection && typeof connection.bind === 'function') {
  // If the socket is already up when this module loads, treat the next
  // "connected" as a reconnection rather than the initial handshake.
  if (connection.state === 'connected') {
    hasConnected = true
  }

  connection.bind('connected', () => {
    if (!hasConnected) {
      hasConnected = true
      return
    }

    reconnectCallbacks.forEach((callback) => {
      try {
        callback()
      } catch (error) {
        console.error('Realtime reconnect handler failed:', error)
      }
    })
  })
}

/**
 * Register a callback invoked after the websocket reconnects.
 * Returns a function that removes the callback.
 */
export function onRealtimeReconnect(callback) {
  reconnectCallbacks.add(callback)

  return () => reconnectCallbacks.delete(callback)
}

/**
 * Subscribe to issue events for a set of projects and invoke a handler for
 * each. Returns a function that tears down every subscription.
 *
 * projectIds: numeric project ids to listen on (private-project.{id}).
 * events:     broadcast event names (e.g. "issue.created").
 * handler:    called once per matching event.
 */
export function subscribeToProjects(projectIds, events, handler) {
  if (!echo || !Array.isArray(projectIds)) return () => {}

  const unsubs = projectIds
    .filter((id) => id)
    .map((id) => {
      const channel = echo.private(`project.${id}`)

      events.forEach((event) => channel.listen(event, handler))

      return () => {
        events.forEach((event) => channel.stopListening(event))
        echo.leave(`project.${id}`)
      }
    })

  return () => unsubs.forEach((unsub) => unsub())
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

export default echo
