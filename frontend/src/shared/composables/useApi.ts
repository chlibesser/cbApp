import { ref } from 'vue'
import type { ApiError } from '../../core/api'

export function useApi<T>() {
  const data = ref<T | null>(null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  const execute = async (apiCall: () => Promise<T>) => {
    loading.value = true
    error.value = null
    
    try {
      const result = await apiCall()
      data.value = result
      return result
    } catch (err) {
      error.value = err as ApiError
      throw err
    } finally {
      loading.value = false
    }
  }

  const reset = () => {
    data.value = null
    error.value = null
    loading.value = false
  }

  return {
    data,
    loading,
    error,
    execute,
    reset
  }
}