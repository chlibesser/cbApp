import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Tenant } from '../../domains/admin'
import { tenantService } from '../../domains/admin'

export const useTenantStore = defineStore('tenant', () => {
  // State
  const tenants = ref<Tenant[]>([])
  const currentTenant = ref<Tenant | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Actions
  const loadTenants = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await tenantService.getTenants()
      tenants.value = response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load tenants'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTenant = async (tenantData: { name: string; subdomain: string }) => {
    try {
      const newTenant = await tenantService.createTenant(tenantData)
      tenants.value.push(newTenant)
      return newTenant
    } catch (err: any) {
      error.value = err.message || 'Failed to create tenant'
      throw err
    }
  }

  const updateTenant = async (id: number, tenantData: Partial<Tenant>) => {
    try {
      const updatedTenant = await tenantService.updateTenant(id, tenantData)
      const index = tenants.value.findIndex((t) => t.id === id)
      if (index !== -1) {
        tenants.value[index] = updatedTenant
      }
      return updatedTenant
    } catch (err: any) {
      error.value = err.message || 'Failed to update tenant'
      throw err
    }
  }

  const deleteTenant = async (id: number) => {
    try {
      await tenantService.deleteTenant(id)
      tenants.value = tenants.value.filter((t) => t.id !== id)
    } catch (err: any) {
      error.value = err.message || 'Failed to delete tenant'
      throw err
    }
  }

  const setCurrentTenant = (tenant: Tenant | null) => {
    currentTenant.value = tenant
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    tenants,
    currentTenant,
    loading,
    error,

    // Actions
    loadTenants,
    createTenant,
    updateTenant,
    deleteTenant,
    setCurrentTenant,
    clearError,
  }
})
