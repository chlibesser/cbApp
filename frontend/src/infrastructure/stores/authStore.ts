import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Account, Profile, LoginCredentials, AuthState } from '../../core/auth'
import { authService } from '../../core/auth'

export const useAuthStore = defineStore('auth', () => {
  // State
  const account = ref<Account | null>(null)
  const profile = ref<Profile | null>(null)
  const token = ref<string | null>(authService.getToken())

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const user = computed(() => ({
    account: account.value,
    profile: profile.value
  }))

  // Actions
  const login = async (credentials: LoginCredentials) => {
    try {
      const response = await authService.login(credentials)
      
      token.value = response.token
      account.value = response.account
      profile.value = response.profile
      
      return response
    } catch (error) {
      // Clear any existing auth data on login failure
      await logout()
      throw error
    }
  }

  const logout = async () => {
    try {
      if (token.value) {
        await authService.logout()
      }
    } catch (error) {
      console.warn('Logout API call failed:', error)
    } finally {
      // Always clear local state and token
      token.value = null
      account.value = null
      profile.value = null
      authService.removeToken()
    }
  }

  const loadUserData = async () => {
    if (!token.value) return

    try {
      const userData = await authService.me()
      account.value = userData.account
      profile.value = userData.profile
    } catch (error) {
      // If loading user data fails, clear auth state
      await logout()
      throw error
    }
  }

  const updateProfile = (updatedProfile: Profile) => {
    profile.value = updatedProfile
  }

  // Initialize auth state
  const initialize = async () => {
    if (token.value && !account.value) {
      try {
        await loadUserData()
      } catch (error) {
        console.warn('Failed to initialize auth state:', error)
      }
    }
  }

  return {
    // State
    account,
    profile,
    token,
    
    // Getters
    isAuthenticated,
    user,
    
    // Actions
    login,
    logout,
    loadUserData,
    updateProfile,
    initialize
  }
})