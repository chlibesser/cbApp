import type { 
  LoginCredentials, 
  LoginResponse, 
  Account, 
  Profile,
  QuickLoginResponse,
  QuickLoginListResponse
} from './types'
import { apiClient } from '../api/apiClient'

export class AuthService {
  private static readonly TOKEN_KEY = 'auth_token'
  private static readonly USER_DATA_KEY = 'auth_user_data'

  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', credentials)
    this.setToken(response.data.token)
    this.setUserData(response.data)
    return response.data
  }

  async logout(): Promise<void> {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      this.removeToken()
      this.removeUserData()
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

  setUserData(userData: any): void {
    localStorage.setItem(AuthService.USER_DATA_KEY, JSON.stringify(userData))
  }

  getUserData(): any | null {
    const data = localStorage.getItem(AuthService.USER_DATA_KEY)
    return data ? JSON.parse(data) : null
  }

  removeUserData(): void {
    localStorage.removeItem(AuthService.USER_DATA_KEY)
  }

  isAuthenticated(): boolean {
    return !!this.getToken()
  }

  // Quick Login Methods
  async getQuickLogins(): Promise<QuickLoginListResponse> {
    const response = await apiClient.get<QuickLoginListResponse>('/auth/quick-login')
    return response.data
  }

  async quickLogin(username: string, tenantId?: number): Promise<QuickLoginResponse> {
    const response = await apiClient.post<QuickLoginResponse>('/auth/quick-login', {
      username,
      tenant_id: tenantId,
    })
    this.setToken(response.data.token)
    this.setUserData(response.data)
    return response.data
  }

  async register(registerData: any): Promise<any> {
    const response = await apiClient.post('/auth/register', registerData)
    this.setToken(response.data.token)
    this.setUserData(response.data)
    return response.data
  }
}

export const authService = new AuthService()
