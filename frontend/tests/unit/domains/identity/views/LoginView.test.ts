import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/domains/identity/views/LoginView.vue'
import { useAuthStore } from '@/infrastructure/stores/authStore'

// Mock all Vuetify components
vi.mock('vuetify/components', () => ({}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } },
    { path: '/dashboard', component: { template: '<div>Dashboard</div>' } },
    { path: '/auth/login', component: LoginView }
  ]
})

describe('LoginView Logic', () => {
  let wrapper: any
  let authStore: any

  beforeEach(async () => {
    await router.push('/auth/login')
    await router.isReady()

    wrapper = mount(LoginView, {
      global: {
        plugins: [
          createTestingPinia({
            createSpy: vi.fn
          }),
          router
        ],
        stubs: {
          VContainer: true,
          VRow: true,
          VCol: true,
          VForm: true,
          VTextField: true,
          VBtn: true,
          VAlert: true,
          VProgressCircular: true,
          QuickLoginHelper: true,
          RouterLink: true
        }
      }
    })

    authStore = useAuthStore()
  })

  afterEach(() => {
    wrapper.unmount()
  })

  it('renders the login view component', () => {
    expect(wrapper.exists()).toBe(true)
    expect(wrapper.vm).toBeDefined()
  })

  it('initializes with correct default state', () => {
    expect(wrapper.vm.credentials).toEqual({
      identifier: '',
      password: ''
    })
    expect(wrapper.vm.isFormValid).toBe(false)
    expect(wrapper.vm.isLoading).toBe(false)
    expect(wrapper.vm.errorMessage).toBe('')
  })

  it('validates identifier field rules', () => {
    const rules = wrapper.vm.identifierRules
    
    // Required validation
    expect(rules[0]('')).toBe('Username or Email is required')
    expect(rules[0]('test')).toBe(true)
    
    // Length validation
    expect(rules[1]('ab')).toBe('Must be at least 3 characters')
    expect(rules[1]('abc')).toBe(true)
    expect(rules[1]('test@example.com')).toBe(true)
  })

  it('validates password field rules', () => {
    const rules = wrapper.vm.passwordRules
    
    // Required validation
    expect(rules[0]('')).toBe('Password is required')
    expect(rules[0]('test')).toBe(true)
    
    // Length validation
    expect(rules[1]('12345')).toBe('Password must be at least 6 characters')
    expect(rules[1]('123456')).toBe(true)
  })

  it('calls auth store login with correct credentials', async () => {
    authStore.login = vi.fn().mockResolvedValue({})
    const push = vi.spyOn(router, 'push')
    
    // Set up valid form state
    wrapper.vm.credentials.identifier = 'testuser'
    wrapper.vm.credentials.password = 'password123'
    wrapper.vm.isFormValid = true
    
    await wrapper.vm.handleLogin()
    
    expect(authStore.login).toHaveBeenCalledWith({
      identifier: 'testuser',
      password: 'password123'
    })
    expect(push).toHaveBeenCalledWith('/dashboard')
  })

  it('prevents login when form is invalid', async () => {
    authStore.login = vi.fn()
    wrapper.vm.isFormValid = false
    
    await wrapper.vm.handleLogin()
    
    expect(authStore.login).not.toHaveBeenCalled()
  })

  it('handles login errors correctly', async () => {
    const errorMessage = 'Invalid credentials'
    authStore.login = vi.fn().mockRejectedValue({ message: errorMessage })
    
    wrapper.vm.credentials.identifier = 'wrong@email.com'
    wrapper.vm.credentials.password = 'wrongpass'
    wrapper.vm.isFormValid = true
    
    await wrapper.vm.handleLogin()
    
    expect(wrapper.vm.errorMessage).toBe(errorMessage)
    expect(wrapper.vm.isLoading).toBe(false)
  })

  it('handles generic login errors', async () => {
    authStore.login = vi.fn().mockRejectedValue(new Error())
    
    wrapper.vm.credentials.identifier = 'testuser'
    wrapper.vm.credentials.password = 'password123'
    wrapper.vm.isFormValid = true
    
    await wrapper.vm.handleLogin()
    
    expect(wrapper.vm.errorMessage).toBe('Login failed')
  })

  it('manages loading state during login', async () => {
    authStore.login = vi.fn().mockImplementation(
      () => new Promise(resolve => setTimeout(resolve, 50))
    )
    
    wrapper.vm.credentials.identifier = 'testuser'
    wrapper.vm.credentials.password = 'password123'
    wrapper.vm.isFormValid = true
    
    const loginPromise = wrapper.vm.handleLogin()
    
    // Should be loading during login
    expect(wrapper.vm.isLoading).toBe(true)
    
    await loginPromise
    
    // Should not be loading after login
    expect(wrapper.vm.isLoading).toBe(false)
  })

  it('clears error message when set to empty string', async () => {
    wrapper.vm.errorMessage = 'Some error'
    await wrapper.vm.$nextTick()
    
    wrapper.vm.errorMessage = ''
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.errorMessage).toBe('')
  })
})