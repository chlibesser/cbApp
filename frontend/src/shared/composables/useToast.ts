import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

/**
 * Vue-kompatibles Toast-System basierend auf dem LayoutStore
 * 
 * Bietet eine einfache API für Toast-Benachrichtigungen mit
 * voller Integration in das cbApp V1 Notification-System.
 */
export function useToast() {
  const layoutStore = useLayoutStore()
  
  return {
    /**
     * Zeigt eine Erfolgs-Nachricht an
     */
    success: (message: string, options?: { timeout?: number; actions?: any[] }) => {
      return layoutStore.showSuccess(message, options)
    },

    /**
     * Zeigt eine Fehler-Nachricht an  
     */
    error: (message: string, options?: { timeout?: number; persistent?: boolean; actions?: any[] }) => {
      return layoutStore.showError(message, options)
    },

    /**
     * Zeigt eine Warn-Nachricht an
     */
    warning: (message: string, options?: { timeout?: number; actions?: any[] }) => {
      return layoutStore.showWarning(message, options)
    },

    /**
     * Zeigt eine Info-Nachricht an
     */
    info: (message: string, options?: { timeout?: number; actions?: any[] }) => {
      return layoutStore.showInfo(message, options)
    },

    /**
     * Entfernt eine spezifische Benachrichtigung
     */
    remove: (id: string) => {
      layoutStore.removeNotification(id)
    },

    /**
     * Entfernt alle Benachrichtigungen
     */
    clear: () => {
      layoutStore.clearAllNotifications()
    },

    /**
     * Direkter Zugriff auf das Notification-Array (readonly)
     */
    notifications: layoutStore.notifications,
  }
}