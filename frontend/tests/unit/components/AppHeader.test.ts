import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import { createVuetify } from 'vuetify'
import AppHeader from '@/shared/components/AppHeader.vue'

const vuetify = createVuetify()

describe('AppHeader', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(AppHeader, {
      global: {
        plugins: [
          createTestingPinia(),
          vuetify
        ]
      }
    })
  })

  it('renders the component', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays the app title', () => {
    expect(wrapper.text()).toContain('cbApp')
  })

  // Weitere Tests je nach AppHeader-Funktionalität
})