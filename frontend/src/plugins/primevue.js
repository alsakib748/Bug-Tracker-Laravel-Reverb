// PrimeVue and its Aura theme preset (~108 KB raw) are only needed by pages
// that render PrimeVue components. The app shell and the dashboard use none of
// them, so the heavy module (./primevue-install) is imported on demand from the
// router guard for the routes that need it, instead of being bundled into the
// entry chunk or downloaded for every navigation. The plugin is installed
// before the matching route renders, so components never flash unstyled.
let appInstance = null
let installPromise = null

export function setPrimeVueApp(app) {
  appInstance = app
}

export function ensurePrimeVue() {
  if (!installPromise) {
    installPromise = import('./primevue-install').then(({ default: plugin }) => {
      appInstance.use(plugin)
    })
  }
  return installPromise
}
