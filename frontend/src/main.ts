import { createApp } from 'vue'
import App from './App.vue'
import router from './core/router'
import { pinia, vuetify, i18n } from './infrastructure/plugins'
import { useAuthStore } from './infrastructure/stores'
import { useLocaleStore } from './core/localization/stores/localeStore'
import './assets/styles/chips.css'

const app = createApp(App)

app.use(pinia)
app.use(i18n)
app.use(router)
app.use(vuetify)

// Initialize auth store (lightweight - following V0 pattern)
// Only loads token from localStorage, no API calls to avoid race conditions
const authStore = useAuthStore()
authStore.initialize()

// Initialize locale store (loads default locale and user preference)
const localeStore = useLocaleStore()

;(async () => {
  try {
    await localeStore.initialize()
  } catch (error) {
    console.error('[main.ts] Failed to initialize locale store:', error)
  } finally {
    // Mount app AFTER translations are loaded
    app.mount('#app')

    // Remove initial HTML loading overlay
    const initialLoading = document.getElementById('initial-loading')
    if (initialLoading) {
      initialLoading.style.display = 'none'
    }
  }
})()
