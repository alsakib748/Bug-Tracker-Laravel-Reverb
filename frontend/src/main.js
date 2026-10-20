import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vue3Toastify from 'vue3-toastify' // 👈 Import Toastify
import 'vue3-toastify/dist/index.css' // 👈 Import Toastify CSS
import App from './App.vue'
import router from './router'
import { setPrimeVueApp } from './plugins/primevue'

// PrimeIcons
import 'primeicons/primeicons.css'

// The Echo/Reverb client is created lazily on first realtime subscription
// (see services/echo.js) so pusher-js is no longer paid for at startup.

const app = createApp(App)
const pinia = createPinia()

// PrimeVue + the Aura theme are code-split (see plugins/primevue.js) and only
// loaded for routes that actually render PrimeVue components — the router
// guard awaits ensurePrimeVue() before those routes mount. Passing the app
// here lets the guard install the plugin on demand rather than at startup.
setPrimeVueApp(app)

app.use(pinia)
app.use(router)

// ApexCharts is only pulled in by the lazily-loaded Analytics page, which
// imports it directly; the dashboard trend chart is dependency-free SVG.
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  clearOnUrlChange: false,
})

// Keep the inline splash (index.html) painted until the router's first
// navigation — including the async auth guard, and the on-demand PrimeVue
// install for routes that need it — has resolved, then mount so the real
// shell replaces it directly instead of flashing an empty page.
router.isReady().finally(() => {
  app.mount('#app')
})
