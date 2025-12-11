import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Account, Profile, LoginCredentials, AuthState, Tenant, QuickLoginAccount } from '../../core/auth'
import { authService } from '../../core/auth'

export const useAuthStore = defineStore('auth', () => {
  // State
  const account = ref<Account | null>(null)
  const profile = ref<Profile | null>(null)
  const token = ref<string | null>(authService.getToken())
  const currentTenant = ref<Tenant | null>(null)
  const availableQuickLogins = ref<QuickLoginAccount[]>([])
  const quickLogins = ref<QuickLoginAccount[]>([])

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const user = computed(() => {
    if (!account.value && !profile.value) return null
    return {
      account: account.value,
      profile: profile.value,
    }
  })

  // Actions
  const login = async (credentials: LoginCredentials) => {
    try {
      const response = await authService.login(credentials)

      token.value = response.token
      account.value = response.account
      profile.value = response.profile
      authService.setToken(response.token)

      return response
    } catch (error) {
      // Clear any existing auth data on login failure
      await logout()
      throw error
    }
  }

  const logout = async () => {
    try {
      // Sofort den Auth-Zustand auf false setzen
      token.value = null
      authService.removeToken()
      
      if (account.value) {
        // Nur API-Call machen wenn wir eingeloggt waren
        await authService.logout()
      }
    } catch (error) {
      console.warn('Logout API call failed:', error)
    } finally {
      // Always clear local state and token
      account.value = null
      profile.value = null
      currentTenant.value = null
      quickLogins.value = []
    }
  }

  const loadUserData = async () => {
    if (!token.value) return

    try {
      const userData = await authService.me()
      account.value = userData.account
      if (userData.profile) {
        profile.value = userData.profile
      }
      if (userData.current_tenant) {
        currentTenant.value = userData.current_tenant
      }
    } catch (error) {
      // If loading user data fails, clear auth state
      await logout()
      throw error
    }
  }

  const updateProfile = (updatedProfile: Profile) => {
    profile.value = updatedProfile
  }

  // Quick Login Actions
  const loadQuickLogins = async () => {
    try {
      const response = await authService.getQuickLogins()
      availableQuickLogins.value = response.accounts
      quickLogins.value = response.accounts
    } catch (error) {
      console.warn('Failed to load quick logins:', error)
      availableQuickLogins.value = []
      quickLogins.value = []
    }
  }

  const quickLogin = async (username: string, tenantId?: number) => {
    try {
      const response = await authService.quickLogin(username, tenantId)

      token.value = response.token
      account.value = response.account
      profile.value = response.profile
      currentTenant.value = response.current_tenant
      authService.setToken(response.token)

      return response
    } catch (error) {
      await logout()
      throw error
    }
  }

  const register = async (registerData: any) => {
    try {
      const response = await authService.register(registerData)

      token.value = response.token
      account.value = response.account
      authService.setToken(response.token)
      
      // Load full user data after registration
      await loadUserData()

      return response
    } catch (error) {
      await logout()
      throw error
    }
  }

  // Tenant Management
  const switchTenant = (tenant: Tenant) => {
    currentTenant.value = tenant
  }

  const hasPermission = (permission: string): boolean => {
    if (!currentTenant.value?.role?.permissions) return false
    return currentTenant.value.role.permissions.includes(permission)
  }

  const canAccess = (resource: string, action: string): boolean => {
    return hasPermission(`${resource}.${action}`)
  }

  const canAccessResource = (resource: string): boolean => {
    if (!currentTenant.value?.role?.permissions) return false
    return currentTenant.value.role.permissions.some((permission: string) => 
      permission.startsWith(`${resource}.`)
    )
  }

  // Initialize auth state (lightweight - similar to V0's approach)
  const initialize = () => {
    token.value = authService.getToken()
    // Don't automatically load user data - this can cause race conditions
    // User data will be loaded after successful navigation by App.vue
  }

  return {
    // State
    account,
    profile,
    token,
    currentTenant,
    availableQuickLogins,
    quickLogins,

    // Getters
    isAuthenticated,
    user,

    // Actions
    login,
    logout,
    register,
    loadUserData,
    updateProfile,
    initialize,

    // Quick Login Actions
    loadQuickLogins,
    quickLogin,

    // Tenant Management
    switchTenant,
    hasPermission,
    canAccess,
    canAccessResource,
  }
})
