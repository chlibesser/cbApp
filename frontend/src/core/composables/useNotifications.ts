import { ref } from 'vue'

// Global notification state
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

/**
 * Global notification composable to replace the old toast system
 * This provides unified success/error messages across the entire app
 */
export function useNotifications() {
  
  function showSuccess(message: string) {
    snackbarText.value = message
    snackbarColor.value = 'success'
    snackbar.value = true
  }

  function showError(message: string) {
    snackbarText.value = message
    snackbarColor.value = 'error'
    snackbar.value = true
  }

  function showWarning(message: string) {
    snackbarText.value = message
    snackbarColor.value = 'warning'
    snackbar.value = true
  }

  function showInfo(message: string) {
    snackbarText.value = message
    snackbarColor.value = 'info'
    snackbar.value = true
  }

  function hideNotification() {
    snackbar.value = false
  }

  return {
    // State (reactive)
    snackbar,
    snackbarText,
    snackbarColor,
    
    // Methods
    showSuccess,
    showError,
    showWarning,
    showInfo,
    hideNotification
  }
}