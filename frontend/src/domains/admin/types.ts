export interface Tenant {
  id: number
  name: string
  subdomain: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Account {
  id: number
  email: string
  email_verified_at?: string
  created_at: string
  updated_at: string
}

export interface CreateTenantRequest {
  name: string
  subdomain: string
}

export interface UpdateTenantRequest {
  name?: string
  subdomain?: string
  is_active?: boolean
}

export interface CreateAccountRequest {
  email: string
  password: string
}
