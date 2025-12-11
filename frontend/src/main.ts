import { createApp } from 'vue'
import App from './App.vue'
import router from './core/router'
import { pinia, vuetify } from './infrastructure/plugins'
import { useAuthStore } from './infrastructure/stores'
import './assets/styles/chips.css'

const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(vuetify)

// Initialize auth store (lightweight - following V0 pattern)
// Only loads token from localStorage, no API calls to avoid race conditions
const authStore = useAuthStore()
authStore.initialize()

app.mount('#app')
