// Tenant Types
export interface Tenant {
  id: string
  name: string
  slug: string
  description?: string
  logo_url?: string
  is_personal: boolean
  is_active: boolean
  settings?: Record<string, any>
  users_count?: number
  max_users?: number
  current_users_count?: number
  created_at: string
  updated_at: string
}

export interface CreateTenantRequest {
  name: string
  slug?: string
  description?: string
  is_personal?: boolean
  is_active?: boolean
  settings?: Record<string, any>
}

export interface UpdateTenantRequest {
  name?: string
  slug?: string
  description?: string
  is_personal?: boolean
  is_active?: boolean
  settings?: Record<string, any>
}

// Account Types
export interface Account {
  id: string
  username: string
  email: string
  email_verified_at?: string
  system_role: string | null
  is_active: boolean
  tenants_count?: number
  last_login_at?: string
  created_at: string
  updated_at: string
  tenants?: Array<{
    id: string
    name: string
    slug: string
    is_personal: boolean
    is_active: boolean
  }>
}

export interface CreateAccountRequest {
  username: string
  email: string
  password: string
  password_confirmation: string
  system_role?: string | null
  is_active?: boolean
}

export interface UpdateAccountRequest {
  username?: string
  email?: string
  password?: string
  password_confirmation?: string
  system_role?: string | null
  is_active?: boolean
}

// Tenant User Management Types
export interface TenantUser {
  id: string
  first_name: string
  last_name: string
  email: string
  phone?: string
  system_role: string
  is_active: boolean
  created_at: string
  account: {
    id: string
    username: string
    email: string
    is_active: boolean
  }
}

export interface AssignUserToTenantRequest {
  account_id: string
  system_role: 'tenant_admin' | 'tenant_member'
  first_name: string
  last_name: string
  email: string
  phone?: string
}

export interface SystemRole {
  value: string
  text: string
  description: string
}
