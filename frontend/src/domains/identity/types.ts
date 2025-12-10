export interface User {
  id: number
  email: string
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

export interface UpdateProfileRequest {
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
}
