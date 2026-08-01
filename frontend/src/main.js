import './assets/main.css'
// Import Swiper styles
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'jsvectormap/dist/jsvectormap.css'
import 'flatpickr/dist/flatpickr.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vue3Toastify from 'vue3-toastify' // 👈 Import Toastify
import 'vue3-toastify/dist/index.css' // 👈 Import Toastify CSS
import App from './App.vue'
import router from './router'
import VueApexCharts from 'vue3-apexcharts'

// PrimeVue Import
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'

// PrimeIcons
import 'primeicons/primeicons.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.dark',
    },
  },
})
app.use(VueApexCharts)
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  clearOnUrlChange: false,
})

app.mount('#app')
