export interface Account {
  id: string
  username: string
  email: string
  is_active: boolean
  email_verified_at?: string
  created_at: string
  updated_at: string
}

export interface Profile {
  id: number
  account_id: number
  tenant_id: number
  first_name: string
  last_name: string
  email: string
  phone?: string
  created_at: string
  updated_at: string
}

export interface AuthState {
  isAuthenticated: boolean
  account: Account | null
  profile: Profile | null
  token: string | null
}

export interface LoginCredentials {
  identifier: string
  password: string
}

export interface LoginResponse {
  token: string
  account: Account
  profile: Profile
}

export interface Role {
  id: number
  name: string
  description?: string
  permissions: string[]
}

export interface Tenant {
  id: number
  name: string
  slug: string
  is_personal: boolean
  role?: Role
}

export interface QuickLoginAccount {
  id: string
  username: string
  email: string
  tenants: Tenant[]
}

export interface QuickLoginResponse {
  message: string
  account: Account
  current_tenant?: Tenant
  token: string
}

export interface QuickLoginListResponse {
  accounts: QuickLoginAccount[]
  environment: string
}
