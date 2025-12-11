import type { 
  Tenant, 
  CreateTenantRequest, 
  UpdateTenantRequest,
  TenantUser,
  AssignUserToTenantRequest
} from '../types'
import type { PaginatedResponse } from '../../../core/api'
import { apiClient } from '../../../core/api'

export class TenantService {
  // Basic CRUD operations
  async getTenants(page: number = 1): Promise<PaginatedResponse<Tenant>> {
    const response = await apiClient.get<PaginatedResponse<Tenant>>(`/admin/tenants?page=${page}`)
    return response.data
  }

  async getTenant(id: string): Promise<Tenant> {
    const response = await apiClient.get<{ tenant: Tenant }>(`/admin/tenants/${id}`)
    return response.data.tenant
  }

  async createTenant(data: CreateTenantRequest): Promise<Tenant> {
    const response = await apiClient.post<{ tenant: Tenant, message: string }>('/admin/tenants', data)
    return response.data.tenant
  }

  async updateTenant(id: string, data: UpdateTenantRequest): Promise<Tenant> {
    const response = await apiClient.put<{ tenant: Tenant, message: string }>(`/admin/tenants/${id}`, data)
    return response.data.tenant
  }

  async deleteTenant(id: string): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`/admin/tenants/${id}`)
    return response.data
  }

  // User management within tenants
  async getTenantUsers(tenantId: string): Promise<{ tenant: Tenant, users: TenantUser[] }> {
    const response = await apiClient.get<{ tenant: Tenant, users: TenantUser[] }>(`/admin/tenants/${tenantId}/users`)
    return response.data
  }

  async assignUserToTenant(tenantId: string, data: AssignUserToTenantRequest): Promise<{ profile: any, message: string }> {
    const response = await apiClient.post<{ profile: any, message: string }>(`/admin/tenants/${tenantId}/users`, data)
    return response.data
  }

  async removeUserFromTenant(tenantId: string, profileId: string): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`/admin/tenants/${tenantId}/users/${profileId}`)
    return response.data
  }

  async updateUserRole(tenantId: string, profileId: string, systemRole: string): Promise<{ profile: any, message: string }> {
    const response = await apiClient.put<{ profile: any, message: string }>(`/admin/tenants/${tenantId}/users/${profileId}/role`, {
      system_role: systemRole
    })
    return response.data
  }

  // System roles
  async getSystemRoles(): Promise<{ roles: Array<{ value: string, text: string, description: string }> }> {
    const response = await apiClient.get<{ roles: Array<{ value: string, text: string, description: string }> }>('/admin/system-roles')
    return response.data
  }
}

export const tenantService = new TenantService()
