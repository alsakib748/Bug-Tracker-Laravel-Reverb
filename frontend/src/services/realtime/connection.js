// Reconnect signalling shared between the lazily-created Echo client and any
// view that wants to refetch after a dropped socket.
//
// This module deliberately has NO dependency on `services/echo` (and therefore
// none on pusher-js / laravel-echo). Keeping it dependency-free means importing
// `onRealtimeReconnect` never pulls the websocket stack into the entry bundle;
// only code that actually opens a channel pays for it.
const reconnectCallbacks = new Set()
let hasConnected = false

/**
 * Called by services/echo once the underlying connection emits "connected".
 * The first connection is the initial handshake and is ignored (views already
 * loaded their data on mount); every later one is a genuine reconnection.
 */
export function emitRealtimeConnected() {
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
}

/**
 * Register a callback invoked after the websocket reconnects.
 * Returns a function that removes the callback.
 */
export function onRealtimeReconnect(callback) {
  reconnectCallbacks.add(callback)

  return () => reconnectCallbacks.delete(callback)
}
