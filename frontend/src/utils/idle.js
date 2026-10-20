// Defer non-critical work until the browser is idle.
//
// If the document is still loading we wait for the `load` event first, so this
// work (websocket handshakes, chart libraries, telemetry, ...) never competes
// with the initial render or the largest contentful paint.
export function runWhenIdle(callback, { timeout = 3000, fallbackDelay = 800 } = {}) {
  const schedule = () => {
    if (typeof window.requestIdleCallback === 'function') {
      window.requestIdleCallback(() => callback(), { timeout })
    } else {
      setTimeout(callback, fallbackDelay)
    }
  }

  if (typeof document === 'undefined' || document.readyState === 'complete') {
    schedule()
  } else {
    window.addEventListener('load', schedule, { once: true })
  }
}
