import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/infrastructure/stores/authStore'
import { authService } from '@/core/auth/authService'

// Mock localStorage first
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock authService
vi.mock('@/core/auth/authService', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
    register: vi.fn(),
    me: vi.fn(),
    quickLogin: vi.fn(),
    getQuickLogins: vi.fn(),
    getToken: vi.fn(() => localStorageMock.getItem('auth_token')),
    setToken: vi.fn((token: string) => localStorageMock.setItem('auth_token', token)),
    clearToken: vi.fn(),
    removeToken: vi.fn(() => localStorageMock.removeItem('auth_token'))
  }
}))

describe('AuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('initialization', () => {
    it('initializes with stored token', () => {
      localStorageMock.getItem.mockReturnValue('stored-token')
      
      const authStore = useAuthStore()
      authStore.initialize()
      
      expect(localStorageMock.getItem).toHaveBeenCalledWith('auth_token')
      expect(authStore.token).toBe('stored-token')
      expect(authStore.isAuthenticated).toBe(true)
    })

    it('initializes without stored token', () => {
      localStorageMock.getItem.mockReturnValue(null)
      
      const authStore = useAuthStore()
      authStore.initialize()
      
      expect(authStore.token).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
    })
  })

  describe('login', () => {
    it('handles login success', async () => {
      const mockResponse = {
        token: 'new-auth-token',
        account: {
          id: 'user-1',
          email: 'test@example.com',
          system_role: 'global_admin'
        }
      }
      
      vi.mocked(authService.login).mockResolvedValue(mockResponse)
      
      const authStore = useAuthStore()
      const credentials = { identifier: 'test@example.com', password: 'password123' }
      
      const result = await authStore.login(credentials)
      
      expect(authService.login).toHaveBeenCalledWith(credentials)
      expect(authStore.token).toBe('new-auth-token')
      expect(authStore.account).toEqual(mockResponse.account)
      expect(authStore.isAuthenticated).toBe(true)
      expect(localStorageMock.setItem).toHaveBeenCalledWith('auth_token', 'new-auth-token')
      expect(result).toEqual(mockResponse)
    })

    it('handles login failure', async () => {
      const loginError = new Error('Invalid credentials')
      vi.mocked(authService.login).mockRejectedValue(loginError)
      
      const authStore = useAuthStore()
      const credentials = { identifier: 'wrong@email.com', password: 'wrongpass' }
      
      await expect(authStore.login(credentials)).rejects.toThrow('Invalid credentials')
      
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token')
    })

    it('clears auth state on login error', async () => {
      // Setup initial state
      const authStore = useAuthStore()
      authStore.token = 'old-token'
      authStore.account = { id: 'old-user' }
      
      vi.mocked(authService.login).mockRejectedValue(new Error('Login failed'))
      
      try {
        await authStore.login({ identifier: 'test', password: 'pass' })
      } catch {
        // Expected to throw
      }
      
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
    })
  })

  describe('logout', () => {
    it('clears auth state on logout', async () => {
      vi.mocked(authService.logout).mockResolvedValue()
      
      const authStore = useAuthStore()
      authStore.token = 'auth-token'
      authStore.account = { id: 'user-1' }
      
      await authStore.logout()
      
      expect(authService.logout).toHaveBeenCalled()
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
      expect(authStore.profile).toBeNull()
      expect(authStore.currentTenant).toBeNull()
      expect(authStore.quickLogins).toEqual([])
      expect(authStore.isAuthenticated).toBe(false)
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token')
    })

    it('clears state even if logout API fails', async () => {
      vi.mocked(authService.logout).mockRejectedValue(new Error('Logout failed'))
      
      const authStore = useAuthStore()
      authStore.token = 'auth-token'
      
      await authStore.logout()
      
      expect(authStore.token).toBeNull()
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token')
    })
  })

  describe('register', () => {
    it('handles registration success', async () => {
      const mockResponse = {
        token: 'registration-token',
        account: {
          id: 'new-user-1',
          email: 'new@example.com'
        }
      }
      
      const mockUserData = {
        account: mockResponse.account,
        profile: { id: 'profile-1', first_name: 'Test' },
        current_tenant: null
      }
      
      vi.mocked(authService.register).mockResolvedValue(mockResponse)
      vi.mocked(authService.me).mockResolvedValue(mockUserData)
      
      const authStore = useAuthStore()
      const registerData = {
        username: 'newuser',
        email: 'new@example.com',
        password: 'password123'
      }
      
      const result = await authStore.register(registerData)
      
      expect(authService.register).toHaveBeenCalledWith(registerData)
      expect(authStore.token).toBe('registration-token')
      expect(authStore.account).toEqual(mockUserData)
      expect(result).toEqual(mockResponse)
    })

    it('handles registration failure', async () => {
      const registrationError = new Error('Email already exists')
      vi.mocked(authService.register).mockRejectedValue(registrationError)
      
      const authStore = useAuthStore()
      
      await expect(authStore.register({ email: 'existing@example.com' })).rejects.toThrow('Email already exists')
      
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
    })
  })

  describe('loadUserData', () => {
    it('loads user data with valid token', async () => {
      const mockUser = {
        id: 'user-1',
        email: 'test@example.com',
        profile: {
          first_name: 'Test',
          last_name: 'User'
        }
      }
      
      vi.mocked(authService.me).mockResolvedValue(mockUser)
      
      const authStore = useAuthStore()
      authStore.token = 'valid-token'
      
      await authStore.loadUserData()
      
      expect(authService.me).toHaveBeenCalled()
      expect(authStore.account).toEqual(mockUser)
    })

    it('clears auth on failed user data load', async () => {
      vi.mocked(authService.me).mockRejectedValue(new Error('Unauthorized'))
      
      const authStore = useAuthStore()
      authStore.token = 'invalid-token'
      authStore.account = { id: 'old-user' }
      
      try {
        await authStore.loadUserData()
      } catch {
        // Expected to throw
      }
      
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token')
    })

    it('does not load user data without token', async () => {
      const authStore = useAuthStore()
      authStore.token = null
      
      await authStore.loadUserData()
      
      expect(authService.me).not.toHaveBeenCalled()
    })
  })

  describe('quick login', () => {
    it('loads quick login accounts', async () => {
      const mockQuickLogins = [
        {
          username: 'admin',
          email: 'admin@example.com',
          system_role: 'global_admin',
          tenants: []
        },
        {
          username: 'user',
          email: 'user@example.com', 
          system_role: null,
          tenants: [{ id: '1', name: 'Test Tenant' }]
        }
      ]
      
      vi.mocked(authService.getQuickLogins).mockResolvedValue({ accounts: mockQuickLogins })
      
      const authStore = useAuthStore()
      
      await authStore.loadQuickLogins()
      
      expect(authService.getQuickLogins).toHaveBeenCalled()
      expect(authStore.quickLogins).toEqual(mockQuickLogins)
    })

    it('performs quick login without tenant', async () => {
      const mockResponse = {
        token: 'quick-token',
        account: { id: 'admin-1', email: 'admin@example.com' }
      }
      
      vi.mocked(authService.quickLogin).mockResolvedValue(mockResponse)
      
      const authStore = useAuthStore()
      
      const result = await authStore.quickLogin('admin')
      
      expect(authService.quickLogin).toHaveBeenCalledWith('admin', undefined)
      expect(authStore.token).toBe('quick-token')
      expect(authStore.account).toEqual(mockResponse.account)
      expect(result).toEqual(mockResponse)
    })

    it('performs quick login with tenant', async () => {
      const mockResponse = {
        token: 'quick-token',
        account: { id: 'user-1' },
        current_tenant: { id: 'tenant-1', name: 'Test Tenant' },
        profile: { id: 'profile-1', first_name: 'Test' }
      }
      
      vi.mocked(authService.quickLogin).mockResolvedValue(mockResponse)
      
      const authStore = useAuthStore()
      
      const result = await authStore.quickLogin('user', 'tenant-1')
      
      expect(authService.quickLogin).toHaveBeenCalledWith('user', 'tenant-1')
      expect(authStore.currentTenant).toEqual(mockResponse.current_tenant)
      expect(authStore.profile).toEqual(mockResponse.profile)
    })

    it('handles quick login errors', async () => {
      vi.mocked(authService.quickLogin).mockRejectedValue(new Error('Quick login failed'))
      
      const authStore = useAuthStore()
      
      await expect(authStore.quickLogin('invalid-user')).rejects.toThrow('Quick login failed')
      
      expect(authStore.token).toBeNull()
      expect(authStore.account).toBeNull()
    })
  })

  describe('tenant management', () => {
    it('switches tenant', () => {
      const authStore = useAuthStore()
      const tenant = { id: 'tenant-1', name: 'Test Tenant' }
      
      authStore.switchTenant(tenant)
      
      expect(authStore.currentTenant).toEqual(tenant)
    })
  })

  describe('permissions', () => {
    it('checks tenant permissions', () => {
      const authStore = useAuthStore()
      authStore.currentTenant = {
        id: 'tenant-1',
        role: {
          permissions: ['create_projects', 'edit_projects', 'view_reports']
        }
      }
      
      expect(authStore.hasPermission('create_projects')).toBe(true)
      expect(authStore.hasPermission('view_reports')).toBe(true)
      expect(authStore.hasPermission('delete_projects')).toBe(false)
    })

    it('returns false when no tenant or permissions', () => {
      const authStore = useAuthStore()
      
      expect(authStore.hasPermission('any_permission')).toBe(false)
      
      authStore.currentTenant = { id: 'tenant-1', role: null }
      expect(authStore.hasPermission('any_permission')).toBe(false)
      
      authStore.currentTenant = { id: 'tenant-1', role: { permissions: null } }
      expect(authStore.hasPermission('any_permission')).toBe(false)
    })

    it('checks resource access', () => {
      const authStore = useAuthStore()
      authStore.currentTenant = {
        role: {
          permissions: ['projects.read', 'projects.create', 'reports.read']
        }
      }
      
      expect(authStore.canAccessResource('projects')).toBe(true)
      expect(authStore.canAccessResource('reports')).toBe(true)
      expect(authStore.canAccessResource('admin')).toBe(false)
    })
  })

  describe('computed properties', () => {
    it('computes isAuthenticated correctly', () => {
      const authStore = useAuthStore()
      
      expect(authStore.isAuthenticated).toBe(false)
      
      authStore.token = 'valid-token'
      expect(authStore.isAuthenticated).toBe(true)
      
      authStore.token = null
      expect(authStore.isAuthenticated).toBe(false)
    })

    it('computes user correctly', () => {
      const authStore = useAuthStore()
      
      expect(authStore.user).toBeNull()
      
      const account = { id: 'user-1', email: 'test@example.com' }
      authStore.account = account
      expect(authStore.user).toEqual({ account, profile: null })
    })
  })

  describe('updateProfile', () => {
    it('updates profile data', () => {
      const authStore = useAuthStore()
      const profile = { 
        id: 'profile-1', 
        first_name: 'Test',
        preferences: { theme: 'light' }
      }
      
      authStore.updateProfile(profile)
      
      expect(authStore.profile).toEqual(profile)
    })
  })
})