// Environment configuration
export const env = {
  // API Configuration
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',

  // App Configuration
  appName: import.meta.env.VITE_APP_NAME || 'cbApp V1',
  appVersion: import.meta.env.VITE_APP_VERSION || '1.0.0',

  // Environment
  isDevelopment: import.meta.env.DEV,
  isProduction: import.meta.env.PROD,
  mode: import.meta.env.MODE,

  // Features
  enableDebugLogs: import.meta.env.VITE_ENABLE_DEBUG_LOGS === 'true' || import.meta.env.DEV,
} as const

export type EnvConfig = typeof env
