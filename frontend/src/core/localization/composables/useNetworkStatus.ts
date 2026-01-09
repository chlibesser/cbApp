/**
 * ============================================================================
 * NETWORK STATUS COMPOSABLE - Offline Detection
 * ============================================================================
 *
 * Detects when user goes offline/online and provides reactive state.
 *
 * WHY WE NEED THIS:
 * - Show appropriate UI when offline (cached translations)
 * - Auto-retry API calls when back online
 * - Prevent confusing error messages (user knows they're offline)
 * - Better UX during poor connectivity
 *
 * BROWSER EVENTS:
 * - 'online': Fired when connection restored
 * - 'offline': Fired when connection lost
 *
 * USAGE:
 * const { isOnline, wasOffline } = useNetworkStatus()
 * if (!isOnline.value) {
 *   showOfflineMessage()
 * }
 */

import { ref, onMounted, onUnmounted } from 'vue'

// Network status composable
export function useNetworkStatus() {
  
  // Reactive online status
  const isOnline = ref(navigator.onLine)

  // Flag to track if we were offline
  const wasOffline = ref(false)

  // Handle online event
  function handleOnline() {
    isOnline.value = true

    // If we were offline, show reconnection message
    if (wasOffline.value) {
      console.log('[NetworkStatus] Connection restored')
      wasOffline.value = false
    }
  }

  // Handle offline event
  function handleOffline() {
    isOnline.value = false
    wasOffline.value = true
  }

  // Lifecycle: Setup event listeners
  onMounted(() => {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
  })

  // Lifecycle: Cleanup event listeners
  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
  })

  return {
    isOnline,
    wasOffline,
  }
}
