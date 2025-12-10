import '@testing-library/jest-dom'
import { config } from '@vue/test-utils'

// Global test setup
config.global.stubs = {
  // Stub Vuetify components if needed
  'v-app': true,
  'v-main': true,
  'v-container': true,
  'router-link': true,
  'router-view': true
}

// Mock window.matchMedia für Vuetify
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))