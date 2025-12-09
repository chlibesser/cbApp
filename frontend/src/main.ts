import { createApp } from 'vue'
import App from './App.vue'
import router from './core/router'
import { pinia, vuetify } from './infrastructure/plugins'
import { useAuthStore } from './infrastructure/stores'

const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(vuetify)

// Initialize auth store
const authStore = useAuthStore()
authStore.initialize()

app.mount('#app')