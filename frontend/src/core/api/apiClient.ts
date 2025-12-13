import type { ApiResponse, ApiError } from './types'

export class ApiClient {
  private baseURL: string
  private defaultHeaders: Record<string, string>

  constructor(baseURL: string = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8004/api') {
    this.baseURL = baseURL
    this.defaultHeaders = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }
  }

  private getAuthToken(): string | null {
    return localStorage.getItem('auth_token')
  }

  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<ApiResponse<T>> {
    const url = `${this.baseURL}${endpoint}`

    const headers = {
      ...this.defaultHeaders,
      ...options.headers,
    }

    const token = this.getAuthToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    // Add tenant ID header for tenant-specific requests
    // TODO: Get from auth store instead of hardcoded
    if (endpoint.includes('/tenant/')) {
      headers['X-Tenant-ID'] = '9b5ae261-24a4-48ed-a8bf-bf60ec25afa9' // Demo Firma GmbH
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
        credentials: 'omit', // Pure token auth - keine Cookies
      })

      let data
      const contentType = response.headers.get('content-type')
      if (contentType?.includes('application/json')) {
        data = await response.json()
      } else {
        data = await response.text()
      }

      if (!response.ok) {
        const error: ApiError = {
          message: data.message || `HTTP ${response.status}`,
          status: response.status,
          errors: data.errors,
        }
        throw error
      }

      return {
        data,
        status: response.status,
        statusText: response.statusText,
      }
    } catch (error) {
      if (error instanceof TypeError) {
        throw {
          message: 'Network error or server unavailable',
          status: 0,
        } as ApiError
      }
      throw error
    }
  }

  async get<T>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET' })
  }

  async post<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async postFile<T>(endpoint: string, formData: FormData): Promise<ApiResponse<T>> {
    const url = `${this.baseURL}${endpoint}`

    const headers: Record<string, string> = {
      Accept: 'application/json',
    }

    const token = this.getAuthToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    // Add tenant ID header for tenant-specific requests
    if (endpoint.includes('/tenant/')) {
      headers['X-Tenant-ID'] = '9b5ae261-24a4-48ed-a8bf-bf60ec25afa9' // Demo Firma GmbH
    }

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers,
        body: formData,
        credentials: 'omit', // Pure token auth - keine Cookies
      })

      let data
      const contentType = response.headers.get('content-type')
      if (contentType?.includes('application/json')) {
        data = await response.json()
      } else {
        data = await response.text()
      }

      if (!response.ok) {
        const error: ApiError = {
          message: data.message || `HTTP ${response.status}`,
          status: response.status,
          errors: data.errors,
        }
        throw error
      }

      return {
        data,
        status: response.status,
        statusText: response.statusText,
      }
    } catch (error) {
      if (error instanceof TypeError) {
        throw {
          message: 'Network error or server unavailable',
          status: 0,
        } as ApiError
      }
      throw error
    }
  }

  async put<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async patch<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async delete<T>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'DELETE' })
  }

  async downloadFile(endpoint: string): Promise<Blob> {
    const url = `${this.baseURL}${endpoint}`

    const headers: Record<string, string> = {}

    const token = this.getAuthToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    // Add tenant ID header for tenant-specific requests
    if (endpoint.includes('/tenant/')) {
      headers['X-Tenant-ID'] = '9b5ae261-24a4-48ed-a8bf-bf60ec25afa9' // Demo Firma GmbH
    }

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers,
        credentials: 'omit', // Pure token auth - keine Cookies
      })

      if (!response.ok) {
        let errorData
        try {
          errorData = await response.json()
        } catch {
          errorData = { message: `HTTP ${response.status}` }
        }

        const error: ApiError = {
          message: errorData.message || `HTTP ${response.status}`,
          status: response.status,
          errors: errorData.errors,
        }
        throw error
      }

      return await response.blob()
    } catch (error) {
      if (error instanceof TypeError) {
        throw {
          message: 'Network error or server unavailable',
          status: 0,
        } as ApiError
      }
      throw error
    }
  }
}

export const apiClient = new ApiClient()
