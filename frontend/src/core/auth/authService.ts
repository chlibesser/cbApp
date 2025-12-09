import type { LoginCredentials, LoginResponse, Account, Profile } from './types'
import { apiClient } from '../api/apiClient'

export class AuthService {
  private static readonly TOKEN_KEY = 'auth_token'

  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', credentials)
    this.setToken(response.data.token)
    return response.data
  }

  async logout(): Promise<void> {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      this.removeToken()
    }
  }

  async me(): Promise<{ account: Account; profile: Profile }> {
    const response = await apiClient.get('/auth/me')
    return response.data
  }

  setToken(token: string): void {
    localStorage.setItem(AuthService.TOKEN_KEY, token)
  }

  getToken(): string | null {
    return localStorage.getItem(AuthService.TOKEN_KEY)
  }

  removeToken(): void {
    localStorage.removeItem(AuthService.TOKEN_KEY)
  }

  isAuthenticated(): boolean {
    return !!this.getToken()
  }
}

export const authService = new AuthService()