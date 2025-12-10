import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { useDisplay } from 'vuetify'

interface RightDrawerState {
  open: boolean
  component: string | null
  props: Record<string, any>
  title: string
  width?: string | number
}

export const useLayoutStore = defineStore('layout', () => {
  const { mobile, mdAndDown } = useDisplay()

  // Sidebar state - standardmäßig auf Desktop geöffnet
  const sidebarOpen = ref(!mdAndDown.value)
  const sidebarPermanent = computed(() => !mdAndDown.value)

  // Right drawer (RSD) state
  const rightDrawer = ref<RightDrawerState>({
    open: false,
    component: null,
    props: {},
    title: '',
    width: 600,
  })

  // Dark mode state (synced with Vuetify theme)
  const isDarkMode = ref(false)

  // Notification/Toast state
  const notifications = ref<Array<{
    id: string
    message: string
    type: 'success' | 'error' | 'warning' | 'info'
    timeout: number
    persistent?: boolean
    actions?: Array<{
      text: string
      action: () => void
    }>
  }>>([])

  // Auto-close sidebar on mobile when route changes
  watch(mobile, (isMobile) => {
    if (isMobile) {
      sidebarOpen.value = false
    }
  })

  // Sidebar actions
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const closeSidebar = () => {
    sidebarOpen.value = false
  }

  const openSidebar = () => {
    sidebarOpen.value = true
  }

  // Right drawer actions
  const openRightDrawer = (
    component: string,
    props: Record<string, any> = {},
    options: {
      title?: string
      width?: string | number
    } = {}
  ) => {
    rightDrawer.value = {
      open: true,
      component,
      props,
      title: options.title || 'Details',
      width: options.width || (mobile.value ? '100vw' : 600),
    }
  }

  const closeRightDrawer = () => {
    rightDrawer.value.open = false
    // Clear component after animation completes
    setTimeout(() => {
      rightDrawer.value.component = null
      rightDrawer.value.props = {}
    }, 300)
  }

  const updateRightDrawerProps = (newProps: Record<string, any>) => {
    rightDrawer.value.props = { ...rightDrawer.value.props, ...newProps }
  }

  // Notification actions
  const addNotification = (notification: {
    message: string
    type?: 'success' | 'error' | 'warning' | 'info'
    timeout?: number
    persistent?: boolean
    actions?: Array<{ text: string; action: () => void }>
  }) => {
    const id = `notification-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    
    const newNotification = {
      id,
      type: 'info' as const,
      timeout: 5000,
      persistent: false,
      actions: [],
      ...notification,
    }

    notifications.value.push(newNotification)

    // Auto-remove after timeout
    if (!newNotification.persistent && newNotification.timeout > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.timeout)
    }

    return id
  }

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const clearAllNotifications = () => {
    notifications.value = []
  }

  // Convenience notification methods
  const showSuccess = (message: string, options?: { timeout?: number; actions?: any[] }) => {
    return addNotification({ message, type: 'success', ...options })
  }

  const showError = (message: string, options?: { timeout?: number; persistent?: boolean; actions?: any[] }) => {
    return addNotification({ 
      message, 
      type: 'error', 
      timeout: 0, 
      persistent: true,
      ...options 
    })
  }

  const showWarning = (message: string, options?: { timeout?: number; actions?: any[] }) => {
    return addNotification({ message, type: 'warning', ...options })
  }

  const showInfo = (message: string, options?: { timeout?: number; actions?: any[] }) => {
    return addNotification({ message, type: 'info', ...options })
  }

  // Dark mode actions
  const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value
  }

  const setDarkMode = (dark: boolean) => {
    isDarkMode.value = dark
  }

  // Layout utilities
  const isSidebarVisible = computed(() => {
    return sidebarPermanent.value || sidebarOpen.value
  })

  const getBreakpoint = computed(() => {
    const { xs, sm, md, lg, xl } = useDisplay()
    if (xs.value) return 'xs'
    if (sm.value) return 'sm'
    if (md.value) return 'md'
    if (lg.value) return 'lg'
    if (xl.value) return 'xl'
    return 'md'
  })

  return {
    // State
    sidebarOpen,
    sidebarPermanent,
    rightDrawer,
    isDarkMode,
    notifications,

    // Computed
    isSidebarVisible,
    getBreakpoint,

    // Sidebar actions
    toggleSidebar,
    closeSidebar,
    openSidebar,

    // Right drawer actions
    openRightDrawer,
    closeRightDrawer,
    updateRightDrawerProps,

    // Notification actions
    addNotification,
    removeNotification,
    clearAllNotifications,
    showSuccess,
    showError,
    showWarning,
    showInfo,

    // Dark mode actions
    toggleDarkMode,
    setDarkMode,
  }
})