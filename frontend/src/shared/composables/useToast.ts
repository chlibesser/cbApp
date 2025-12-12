interface ToastOptions {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}

export function useToast() {
  const showToast = (options: ToastOptions) => {
    // For now, just use console logging
    // Later this can be integrated with a proper toast library like Vuetify Snackbar
    const emoji = {
      success: '✅',
      error: '❌', 
      warning: '⚠️',
      info: 'ℹ️'
    }[options.type || 'info']
    
    console.log(`${emoji} ${options.message}`)
    
    // No more alerts - errors are now shown inline in forms
    // Success messages can be console only for now
  }

  // Convenience methods
  const success = (message: string, duration?: number) => {
    showToast({ message, type: 'success', duration })
  }

  const error = (message: string, duration?: number) => {
    showToast({ message, type: 'error', duration })
  }

  const warning = (message: string, duration?: number) => {
    showToast({ message, type: 'warning', duration })
  }

  const info = (message: string, duration?: number) => {
    showToast({ message, type: 'info', duration })
  }

  return {
    showToast,
    success,
    error,
    warning,
    info
  }
}