import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import { de, en } from 'vuetify/locale' // ADD THIS IMPORT
import type { SupportedLocale } from '@/core/localization/types/locale.types' // ADD THIS IMPORT
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

const vuetify = createVuetify({
  components,
  directives,

  // LOCALE CONFIGURATION (NEW)
  locale: {
    locale: (import.meta.env.VITE_DEFAULT_LOCALE || 'de') as SupportedLocale,           // Default locale from env
    fallback: (import.meta.env.VITE_FALLBACK_LOCALE || 'de') as SupportedLocale,         // Fallback from env
    messages: { de, en },   // Vuetify's built-in translations
  },

  // ICONS CONFIGURATION (EXISTING)
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },

  // THEME CONFIGURATION (EXISTING)
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          secondary: '#424242',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107',
        },
      },
      dark: {
        colors: {
          primary: '#2196F3',
          secondary: '#424242',
          accent: '#FF4081',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FB8C00',
        },
      },
    },
  },
})

// Sync Vuetify locale with app locale
export function syncVuetifyLocale(locale: SupportedLocale): void {
  vuetify.locale.current.value = locale
}

export { vuetify }
export default vuetify
