/**
 * RSD (Right Side Drawer) Store
 * Zentrale Verwaltung für Right-Side-Drawer Komponenten
 */

import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'

export type RSDMode = 'view' | 'edit' | 'create'
export type RSDEntity = 'account' | 'tenant' | 'profile' | 'permission' | 'role' | 'user' | 'partner'

interface RSDState {
  isOpen: boolean
  entity: RSDEntity | null
  mode: RSDMode | null
  data: any | null
  loading: boolean
  error: string | null
}

export const useRSDStore = defineStore('rsd', () => {
  // State
  const state = ref<RSDState>({
    isOpen: false,
    entity: null,
    mode: null,
    data: null,
    loading: false,
    error: null
  })

  // Computed
  const isOpen = computed(() => state.value.isOpen)
  const entity = computed(() => state.value.entity)
  const mode = computed(() => state.value.mode)
  const data = computed(() => state.value.data)
  const loading = computed(() => state.value.loading)
  const error = computed(() => state.value.error)

  const isViewMode = computed(() => state.value.mode === 'view')
  const isEditMode = computed(() => state.value.mode === 'edit')
  const isCreateMode = computed(() => state.value.mode === 'create')

  const title = computed(() => {
    if (!state.value.entity) return ''

    const entityNames: Record<RSDEntity, string> = {
      account: 'Account',
      tenant: 'Tenant',
      profile: 'Profile',
      permission: 'Berechtigung',
      role: 'Rolle',
      user: 'Benutzer',
      partner: 'Partner'
    }

    const entityName = entityNames[state.value.entity]

    switch (state.value.mode) {
      case 'view':
        return `${entityName} anzeigen`
      case 'edit':
        return `${entityName} bearbeiten`
      case 'create':
        return `${entityName} erstellen`
      default:
        return entityName
    }
  })

  const subtitle = computed(() => {
    if (!state.value.data || state.value.mode === 'create') return ''

    // Generate subtitle based on entity and data
    switch (state.value.entity) {
      case 'account':
        return state.value.data.username || state.value.data.email || ''
      case 'tenant':
        return state.value.data.name || state.value.data.slug || ''
      case 'profile':
        return `${state.value.data.first_name || ''} ${state.value.data.last_name || ''}`.trim() || state.value.data.email || ''
      case 'user':
        return state.value.data.full_name || state.value.data.email || ''
      case 'permission':
        return state.value.data.name || state.value.data.key || ''
      case 'role':
        return state.value.data.name || ''
      case 'partner':
        return state.value.data.name || ''
      default:
        return ''
    }
  })

  // Actions
  const open = (entity: RSDEntity, mode: RSDMode, data: any = null) => {
    state.value.isOpen = true
    state.value.entity = entity
    state.value.mode = mode
    state.value.data = data
    state.value.error = null
    state.value.loading = false
  }

  const close = () => {
    state.value.isOpen = false
    state.value.entity = null
    state.value.mode = null
    state.value.data = null
    state.value.error = null
    state.value.loading = false
  }

  const setLoading = (loading: boolean) => {
    state.value.loading = loading
  }

  const setError = (error: string | null) => {
    state.value.error = error
  }

  const updateData = (newData: any) => {
    state.value.data = { ...state.value.data, ...newData }
  }

  const setData = (data: any) => {
    state.value.data = data
  }

  // Utility methods
  const openView = (entity: RSDEntity, data: any) => {
    open(entity, 'view', data)
  }

  const openEdit = (entity: RSDEntity, data: any) => {
    open(entity, 'edit', data)
  }

  const openCreate = (entity: RSDEntity) => {
    open(entity, 'create', null)
  }

  // Event handlers for success/failure operations
  const handleSuccess = (message?: string, shouldClose = true) => {
    if (shouldClose) {
      close()
    }

    // Dispatch refresh event
    window.dispatchEvent(new CustomEvent('table-refresh', {
      detail: { 
        entity: state.value.entity,
        action: state.value.mode,
        success: true,
        message
      }
    }))

    // Optional: Show toast notification via layout store
    // This can be handled by the component using the RSD store
  }

  const handleError = (error: string) => {
    setError(error)
    setLoading(false)
  }

  // Additional properties for component access
  const currentItem = computed(() => state.value.data)

  // Emit success event for parent components
  const emitSuccess = (eventType: string, data?: any) => {
    window.dispatchEvent(new CustomEvent('rsd-success', {
      detail: { 
        eventType,
        entity: state.value.entity,
        mode: state.value.mode,
        data
      }
    }))
  }

  return {
    // State
    state: readonly(state),
    
    // Computed
    isOpen,
    entity,
    mode,
    data,
    loading,
    error,
    isViewMode,
    isEditMode,
    isCreateMode,
    title,
    subtitle,

    // Actions
    open,
    close,
    setLoading,
    setError,
    updateData,
    setData,

    // Utility methods
    openView,
    openEdit,
    openCreate,

    // Event handlers
    handleSuccess,
    handleError,

    // Additional properties
    currentItem,
    emitSuccess
  }
})