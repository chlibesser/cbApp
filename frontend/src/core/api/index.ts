export * from './types'
export * from './apiClient'

import { apiClient } from './apiClient'

export function useApi() {
  return apiClient
}
