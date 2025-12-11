/**
 * Tenant Store - Manages current tenant context and available tenants
 */

import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import type { Tenant } from '@/domains/admin/types'
import { tenantService } from '@/domains/admin/services/tenantService'

export const useTenantStore = defineStore('tenant', () => {
  // State
  const currentTenant = ref<Tenant | null>(null)
  const availableTenants = ref<Tenant[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const isLoaded = computed(() => currentTenant.value !== null)
  const tenantName = computed(() => currentTenant.value?.name || 'Kein Tenant')
  const tenantSlug = computed(() => currentTenant.value?.slug || '')
  const isPersonalTenant = computed(() => currentTenant.value?.is_personal || false)

  // Actions
  const setCurrentTenant = (tenant: Tenant | null) => {
    currentTenant.value = tenant
    
    // Store in localStorage for persistence
    if (tenant) {
      localStorage.setItem('currentTenant', JSON.stringify(tenant))
    } else {
      localStorage.removeItem('currentTenant')
    }
  }

  const loadCurrentTenant = () => {
    const stored = localStorage.getItem('currentTenant')
    if (stored) {
      try {
        currentTenant.value = JSON.parse(stored)
      } catch (error) {
        console.warn('Failed to parse stored tenant:', error)
        localStorage.removeItem('currentTenant')
      }
    }
  }

  const switchTenant = async (tenantId: string) => {
    loading.value = true
    error.value = null
    
    try {
      // Find tenant in available tenants
      const tenant = availableTenants.value.find(t => t.id === tenantId)
      
      if (!tenant) {
        throw new Error('Tenant nicht gefunden')
      }

      // For now, we'll just switch the current tenant
      // In a full implementation, this would involve API calls to:
      // 1. Validate access to the tenant
      // 2. Load user profile for this tenant
      // 3. Set authentication context
      
      setCurrentTenant(tenant)
      
      return { success: true, message: `Zu Tenant "${tenant.name}" gewechselt` }
    } catch (err: any) {
      error.value = err.message || 'Fehler beim Wechseln des Tenants'
      throw err
    } finally {
      loading.value = false
    }
  }

  const loadTenants = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await tenantService.getTenants()
      availableTenants.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Fehler beim Laden der Tenants'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTenant = async (tenantData: any) => {
    try {
      const response = await tenantService.createTenant(tenantData)
      availableTenants.value.push(response.tenant)
      return response
    } catch (err: any) {
      error.value = err.message || 'Fehler beim Erstellen des Tenants'
      throw err
    }
  }

  const updateTenant = async (id: string, tenantData: any) => {
    try {
      const response = await tenantService.updateTenant(id, tenantData)
      const index = availableTenants.value.findIndex((t) => t.id === id)
      if (index !== -1) {
        availableTenants.value[index] = response.tenant
      }
      
      // Update current tenant if it's the same one
      if (currentTenant.value?.id === id) {
        setCurrentTenant(response.tenant)
      }
      
      return response
    } catch (err: any) {
      error.value = err.message || 'Fehler beim Aktualisieren des Tenants'
      throw err
    }
  }

  const deleteTenant = async (id: string) => {
    try {
      await tenantService.deleteTenant(id)
      availableTenants.value = availableTenants.value.filter((t) => t.id !== id)
      
      // Clear current tenant if it was deleted
      if (currentTenant.value?.id === id) {
        setCurrentTenant(null)
      }
    } catch (err: any) {
      error.value = err.message || 'Fehler beim Löschen des Tenants'
      throw err
    }
  }

  const setAvailableTenants = (tenants: Tenant[]) => {
    availableTenants.value = tenants
  }

  const addTenant = (tenant: Tenant) => {
    const existingIndex = availableTenants.value.findIndex(t => t.id === tenant.id)
    if (existingIndex >= 0) {
      availableTenants.value[existingIndex] = tenant
    } else {
      availableTenants.value.push(tenant)
    }
  }

  const removeTenant = (tenantId: string) => {
    availableTenants.value = availableTenants.value.filter(t => t.id !== tenantId)
    
    // If the removed tenant was the current one, clear it
    if (currentTenant.value?.id === tenantId) {
      setCurrentTenant(null)
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clear = () => {
    currentTenant.value = null
    availableTenants.value = []
    error.value = null
    localStorage.removeItem('currentTenant')
  }

  // Initialize from localStorage on store creation
  loadCurrentTenant()

  return {
    // State
    currentTenant: readonly(currentTenant),
    availableTenants: readonly(availableTenants),
    loading: readonly(loading),
    error: readonly(error),

    // Computed
    isLoaded,
    tenantName,
    tenantSlug,
    isPersonalTenant,

    // Actions
    setCurrentTenant,
    loadCurrentTenant,
    switchTenant,
    loadTenants,
    createTenant,
    updateTenant,
    deleteTenant,
    setAvailableTenants,
    addTenant,
    removeTenant,
    clearError,
    clear
  }
})
